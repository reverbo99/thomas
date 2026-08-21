<?php

namespace App\Services;

use App\Http\Controllers\SmsController;
use App\Models\AdminWallet;
use App\Models\bus;
use App\Models\Parcel;
use App\Models\Setting;
use App\Models\User;
use App\Models\VenderBalance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Parcel lifecycle: awaiting_payment → registered → received → in_transit → arrived → completed.
 * ClickPesa settles wallets (system / owner / vendor), then TRA + notifications run.
 */
class ParcelFlowService
{
    public const STATUS_AWAITING_PAYMENT = 'awaiting_payment';
    public const STATUS_REGISTERED = 'registered';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_IN_TRANSIT = 'in_transit';
    public const STATUS_ARRIVED = 'arrived';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    /** @deprecated legacy alias for registered */
    public const STATUS_PENDING = 'pending';

    public const PAY_UNPAID = 'unpaid';
    public const PAY_PENDING = 'pending';
    public const PAY_PAID = 'paid';

    /** Vendor share of the non-system remainder when a vendor registered the parcel. */
    public const VENDOR_REMAINDER_PERCENT = 25.0;

    /**
     * Bus-owner wallet share of a paid parcel (same formula as confirmPayment).
     */
    public static function ownerShareAmount(float $amountPaid, $venderId = null, ?float $systemPct = null): float
    {
        if ($systemPct === null) {
            $systemPct = (float) (Setting::first()->parcel_commission_percentage ?? 0);
        }

        $systemShare = round($amountPaid * $systemPct / 100, 2);
        $remainder = round($amountPaid - $systemShare, 2);

        if ($venderId) {
            $vendorShare = round($remainder * self::VENDOR_REMAINDER_PERCENT / 100, 2);

            return round($remainder - $vendorShare, 2);
        }

        return $remainder;
    }

    public function normalizeStatus(?Parcel $parcel): string
    {
        $status = $parcel->status ?? self::STATUS_PENDING;
        if ($status === self::STATUS_PENDING) {
            return ($parcel->payment_status ?? null) === self::PAY_PAID
                ? self::STATUS_REGISTERED
                : self::STATUS_AWAITING_PAYMENT;
        }

        return $status;
    }

    public function assertBusAcceptsParcels(bus $bus): void
    {
        if (!(bool) ($bus->accept_parcels ?? true)) {
            throw new \RuntimeException(__('vender/parcels.bus_not_accepting'));
        }
    }

    /**
     * @throws \RuntimeException when weight would exceed bus capacity
     */
    public function assertCapacity(bus $bus, ?float $newWeight, ?int $excludeParcelId = null): void
    {
        $max = $bus->max_parcel_weight_kg;
        if ($max === null || (float) $max <= 0) {
            return;
        }

        $query = Parcel::where('bus_id', $bus->id)
            ->whereNotIn('status', [self::STATUS_CANCELLED, self::STATUS_COMPLETED, self::STATUS_AWAITING_PAYMENT])
            ->where(function ($q) {
                $q->where('payment_status', self::PAY_PAID)
                    ->orWhereNull('payment_status');
            });

        if ($excludeParcelId) {
            $query->where('id', '!=', $excludeParcelId);
        }

        $used = (float) $query->sum('weight');
        $incoming = (float) ($newWeight ?? 0);
        if (($used + $incoming) > (float) $max + 0.0001) {
            throw new \RuntimeException(__('vender/parcels.capacity_full', [
                'used' => number_format($used, 2),
                'max' => number_format((float) $max, 2),
            ]));
        }
    }

    public function buildPaymentReference(Parcel $parcel): string
    {
        $code = preg_replace('/[^A-Za-z0-9]/', '', (string) $parcel->parcel_number) ?: 'PCL';
        $code = strtoupper(substr($code, 0, 9));

        // ClickPesa requires orderReference to be <= 20 alphanumeric characters,
        // so the timestamp suffix is trimmed to keep code + 'PCL' + suffix within budget.
        $suffix = substr((string) time(), -8);

        return substr($code . 'PCL' . $suffix, 0, 20);
    }

    public function findByPaymentReference(string $reference): ?Parcel
    {
        $sanitized = preg_replace('/[^a-zA-Z0-9]/', '', $reference) ?: $reference;

        $parcel = Parcel::where(function ($q) use ($sanitized, $reference) {
            $q->where('payment_ref', $sanitized)->orWhere('payment_ref', $reference);
        })->first();

        if ($parcel) {
            return $parcel;
        }

        if (preg_match('/^(.+?)PCL\d+$/i', $sanitized, $m)) {
            return Parcel::whereRaw(
                "REPLACE(REPLACE(REPLACE(parcel_number, '-', ''), '_', ''), ' ', '') LIKE ?",
                [$m[1] . '%']
            )->whereIn('payment_status', [self::PAY_PENDING, self::PAY_UNPAID])
                ->orderByDesc('id')
                ->first();
        }

        return null;
    }

    /**
     * Credit wallets and mark parcel paid/registered. Idempotent.
     */
    public function confirmPayment(Parcel $parcel, string $reference, string $method = 'clickpesa'): Parcel
    {
        return DB::transaction(function () use ($parcel, $reference, $method) {
            $parcel = Parcel::whereKey($parcel->id)->lockForUpdate()->first();

            if ($parcel->payment_status === self::PAY_PAID && $parcel->settled_at) {
                return $parcel;
            }

            $amount = (float) $parcel->amount_paid;
            $settings = Setting::first();
            $systemPct = (float) ($settings->parcel_commission_percentage ?? 0);
            $systemShare = round($amount * $systemPct / 100, 2);
            $ownerShare = self::ownerShareAmount($amount, $parcel->vender_id, $systemPct);
            $vendorShare = $parcel->vender_id
                ? round(round($amount - $systemShare, 2) - $ownerShare, 2)
                : 0.0;

            $adminWallet = AdminWallet::find(1) ?: AdminWallet::query()->first();
            if (!$adminWallet) {
                $adminWallet = AdminWallet::create([
                    'service_balance' => 0,
                    'commision_balance' => 0,
                    'balance' => 0,
                    'vat' => 0,
                ]);
            }
            if ($systemShare > 0) {
                $adminWallet->increment('balance', $systemShare);
            }

            $parcel->loadMissing('bus.campany.balance');
            $campany = $parcel->bus?->campany;
            if ($campany && !$campany->balance) {
                $campany->balance()->create([
                    'campany_id' => $campany->id,
                    'amount' => 0,
                    'fees' => 0,
                ]);
                $campany->load('balance');
            }
            if ($campany && $campany->balance && $ownerShare > 0) {
                $campany->balance->increment('amount', $ownerShare);
            }

            if ($parcel->vender_id && $vendorShare > 0) {
                $vb = VenderBalance::firstOrCreate(
                    ['user_id' => $parcel->vender_id],
                    ['amount' => 0]
                );
                if ($vb->amount === null) {
                    $vb->forceFill(['amount' => 0])->save();
                }
                $vb->increment('amount', $vendorShare);
            }

            $parcel->update([
                'payment_status' => self::PAY_PAID,
                'payment_method' => $method,
                'payment_ref' => $reference,
                'status' => self::STATUS_REGISTERED,
                'settled_at' => now(),
            ]);

            Log::info('Parcel payment settled', [
                'parcel_id' => $parcel->id,
                'amount' => $amount,
                'system' => $systemShare,
                'owner' => $ownerShare,
                'vendor' => $vendorShare,
                'reference' => $reference,
            ]);

            return $parcel->fresh(['bus.campany', 'bus.route']);
        });
    }

    public function notifyRegistered(Parcel $parcel): void
    {
        $parcel->loadMissing('bus.campany', 'bus.route');
        $company = $parcel->bus->campany->name ?? 'Highlink';
        $from = $parcel->bus->route->from ?? '';
        $to = $parcel->receiver_delivery_address ?? ($parcel->bus->route->to ?? '');

        $receiverMsg = "Mpendwa {$parcel->receiver_name}, mzigo nambari {$parcel->parcel_number} umepokelewa na {$company} hapa {$from} tayari kusafirishwa kuelekea {$to}. Utapokea taarifa baada ya kuwasili.";
        $senderMsg = "Habari {$parcel->sender_name}, mzigo wako {$parcel->parcel_number} umesajiliwa na {$company}. Mpokeaji: {$parcel->receiver_name}. Kufuatilia: {$parcel->parcel_number}.";

        $this->smsSafe($parcel->receiver_contact_1, $receiverMsg, $parcel->id);
        $this->smsSafe($parcel->sender_contact, $senderMsg, $parcel->id);
        $this->emailSafe($parcel->sender_contact, 'Parcel registered ' . $parcel->parcel_number, $senderMsg);
        $this->emailSafe($parcel->receiver_contact_1, 'Parcel received ' . $parcel->parcel_number, $receiverMsg);
    }

    public function assignReceivingAgent(Parcel $parcel, array $data, User $actor): Parcel
    {
        $parcel->update([
            'receiving_user_id' => $data['receiving_user_id'] ?? null,
            'receiving_agent_name' => $data['receiving_agent_name'] ?? null,
            'receiving_agent_phone' => $data['receiving_agent_phone'] ?? null,
            'delivery_rider_name' => $data['delivery_rider_name'] ?? $parcel->delivery_rider_name,
            'delivery_rider_phone' => $data['delivery_rider_phone'] ?? $parcel->delivery_rider_phone,
            'bus_id' => $data['bus_id'] ?? $parcel->bus_id,
        ]);

        return $parcel->fresh();
    }

    public function markReceived(Parcel $parcel): Parcel
    {
        $status = $this->normalizeStatus($parcel);
        if ($status !== self::STATUS_REGISTERED) {
            throw new \RuntimeException(__('vender/parcels.cannot_receive'));
        }

        if (($parcel->payment_status ?? null) !== self::PAY_PAID) {
            throw new \RuntimeException(__('vender/parcels.cannot_receive_unpaid'));
        }

        $parcel->update([
            'status' => self::STATUS_RECEIVED,
            'received_at' => now(),
        ]);

        $parcel = $parcel->fresh(['bus.campany', 'bus.route']);
        $this->notifyRegistered($parcel);

        return $parcel;
    }

    public function markDeparted(Parcel $parcel): Parcel
    {
        $status = $this->normalizeStatus($parcel);
        if ($status !== self::STATUS_RECEIVED) {
            throw new \RuntimeException(__('vender/parcels.cannot_depart'));
        }

        $parcel->loadMissing('bus');
        $driver = $parcel->bus->driver_name ?? 'N/A';
        $driverPhone = $parcel->bus->driver_contact ?? 'N/A';
        $company = optional($parcel->bus->campany)->name ?? 'Highlink';

        $msg = "Mzigo {$parcel->parcel_number} umeondoka ({$company}). Dereva: {$driver}, simu: {$driverPhone}.";
        $this->smsSafe($parcel->receiver_contact_1, $msg, $parcel->id);
        $this->smsSafe($parcel->sender_contact, $msg, $parcel->id);

        $parcel->update([
            'status' => self::STATUS_IN_TRANSIT,
            'departed_at' => now(),
        ]);

        return $parcel->fresh();
    }

    public function markArrived(Parcel $parcel): Parcel
    {
        if ($this->normalizeStatus($parcel) !== self::STATUS_IN_TRANSIT) {
            throw new \RuntimeException(__('vender/parcels.cannot_arrive'));
        }

        $parcel->update([
            'status' => self::STATUS_ARRIVED,
            'arrived_at' => now(),
        ]);
        $parcel = $parcel->fresh();

        $company = optional($parcel->bus->campany)->name ?? 'Highlink';
        $agent = $parcel->receiving_agent_name ?: $company;
        $agentPhone = $parcel->receiving_agent_phone ?: '';

        if ($parcel->parcel_instructions === 'collection') {
            $msg = "Mpendwa {$parcel->receiver_name}, mzigo {$parcel->parcel_number} umefika. Tafadhali uje ukachukue kwa {$agent}" .
                ($agentPhone ? " ({$agentPhone})" : '') .
                ". Lete nambari ya ufuatiliaji.";
            $this->smsSafe($parcel->receiver_contact_1, $msg, $parcel->id);
        } else {
            $riderPhone = $parcel->delivery_rider_phone;
            $riderMsg = "Delivery: mzigo {$parcel->parcel_number} kwa {$parcel->receiver_name}, anwani: {$parcel->receiver_delivery_address}, simu: {$parcel->receiver_contact_1}.";
            if ($riderPhone) {
                $this->smsSafe($riderPhone, $riderMsg, $parcel->id);
            }
            $this->smsSafe($parcel->receiver_contact_1,
                "Mzigo {$parcel->parcel_number} umefika na unawasili kwa uwasilishaji. Rider: " .
                ($parcel->delivery_rider_name ?: 'N/A') . ' ' . ($riderPhone ?: ''),
                $parcel->id);
        }

        return $parcel;
    }

    public function collect(Parcel $parcel, string $trackingNumber, User $actor): Parcel
    {
        $status = $this->normalizeStatus($parcel);
        if (!in_array($status, [self::STATUS_ARRIVED, self::STATUS_IN_TRANSIT], true)) {
            throw new \RuntimeException(__('vender/parcels.cannot_collect'));
        }

        $expected = preg_replace('/\s+/', '', strtoupper((string) $parcel->parcel_number));
        $given = preg_replace('/\s+/', '', strtoupper(trim($trackingNumber)));
        if ($expected !== $given) {
            throw new \RuntimeException(__('vender/parcels.tracking_mismatch'));
        }

        $parcel->update([
            'status' => self::STATUS_COMPLETED,
            'collected_at' => now(),
        ]);

        $this->smsSafe(
            $parcel->sender_contact,
            "Mzigo {$parcel->parcel_number} umepokelewa na {$parcel->receiver_name}.",
            $parcel->id
        );

        return $parcel->fresh();
    }

    public function statusLabel(?string $status): string
    {
        $status = $status ?: 'pending';
        $key = 'vender/parcels.status_' . $status;
        $t = __($key);

        return $t === $key ? ucfirst(str_replace('_', ' ', $status)) : $t;
    }

    /**
     * True only when ClickPesa (or settlement) confirmed payment.
     * unpaid / pending / null / anything else = not confirmed.
     */
    public function isPaymentConfirmed(?Parcel $parcel): bool
    {
        if (!$parcel) {
            return false;
        }

        return ($parcel->payment_status ?? null) === self::PAY_PAID;
    }

    /**
     * Receipt print is allowed only after payment is confirmed.
     * Blocks: unpaid, pending, awaiting_payment, cancelled, and any non-paid row.
     */
    public function canPrintReceipt(?Parcel $parcel): bool
    {
        if (!$parcel) {
            return false;
        }

        if (!$this->isPaymentConfirmed($parcel)) {
            return false;
        }

        $status = $this->normalizeStatus($parcel);

        return !in_array($status, [
            self::STATUS_AWAITING_PAYMENT,
            self::STATUS_PENDING,
            self::STATUS_CANCELLED,
        ], true);
    }

    private function smsSafe(?string $phone, string $message, int $parcelId): void
    {
        if (empty($phone)) {
            return;
        }
        try {
            (new SmsController())->sms_send($phone, $message);
        } catch (\Throwable $e) {
            Log::warning('Parcel SMS failed', ['parcel_id' => $parcelId, 'error' => $e->getMessage()]);
        }
    }

    private function emailSafe(?string $maybeEmail, string $subject, string $body): void
    {
        if (empty($maybeEmail) || !filter_var($maybeEmail, FILTER_VALIDATE_EMAIL)) {
            return;
        }
        try {
            Mail::raw($body, function ($mail) use ($maybeEmail, $subject) {
                $mail->to($maybeEmail)->subject($subject);
            });
        } catch (\Throwable $e) {
            Log::warning('Parcel email failed', ['to' => $maybeEmail, 'error' => $e->getMessage()]);
        }
    }
}
