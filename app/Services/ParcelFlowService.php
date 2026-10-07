<?php

namespace App\Services;

use App\Http\Controllers\SmsController;
use App\Models\AdminWallet;
use App\Models\bus;
use App\Models\GovernmentLevy;
use App\Models\Parcel;
use App\Models\Setting;
use App\Models\User;
use App\Models\VenderAccount;
use App\Models\VenderBalance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Parcel movement lifecycle:
 *   awaiting_payment → registered → in_store → loaded → received → completed.
 * "received" now means received AT THE DESTINATION; the origin office intake is
 * "in_store". ClickPesa settles wallets (system / owner / vendor), then TRA + notifications run.
 */
class ParcelFlowService
{
    public const STATUS_AWAITING_PAYMENT = 'awaiting_payment';
    public const STATUS_REGISTERED = 'registered';
    /** Received at the origin office / store, not yet on the bus. */
    public const STATUS_IN_STORE = 'in_store';
    /** Loaded on the bus; conductor details captured. */
    public const STATUS_LOADED = 'loaded';
    /** Received at the destination office. */
    public const STATUS_RECEIVED = 'received';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    /** @deprecated legacy alias for registered */
    public const STATUS_PENDING = 'pending';
    /** @deprecated legacy stage folded into STATUS_LOADED */
    public const STATUS_IN_TRANSIT = 'in_transit';
    /** @deprecated legacy stage folded into STATUS_RECEIVED */
    public const STATUS_ARRIVED = 'arrived';

    public const PAY_UNPAID = 'unpaid';
    public const PAY_PENDING = 'pending';
    public const PAY_PAID = 'paid';
    /** A deposit was collected; a balance is still due at the destination. */
    public const PAY_PARTIAL = 'partial';
    /** Cash on delivery: nothing collected yet, the full fee is due on handover. */
    public const PAY_COD = 'cod';

    /** Payment modes chosen when the parcel is registered. */
    public const MODE_CASH = 'cash';
    public const MODE_INSTALMENT = 'instalment';
    public const MODE_COD = 'cod';

    /** Default vendor share of the non-system remainder when a vendor registered the parcel. */
    public const VENDOR_REMAINDER_PERCENT = 25.0;

    /**
     * Parcel commission pool percent (settings), falling back to the ticket commission rate.
     */
    public static function commissionPercent(?Setting $settings = null): float
    {
        $settings = $settings ?? Setting::first();
        $pct = $settings->parcel_commission_percentage ?? null;

        if ($pct === null || $pct === '') {
            return FareFormulaService::DEFAULT_COMMISSION_PERCENT;
        }

        return max(0.0, (float) $pct);
    }

    /**
     * Vendor percent of the parcel commission pool. Same source as ticket commission:
     * the vendor account percentage (default 10), not a cut of the bus-owner remainder.
     */
    public static function vendorPoolPercent($venderId): float
    {
        if (empty($venderId)) {
            return 0.0;
        }

        $pct = VenderAccount::where('user_id', $venderId)->value('percentage');
        if ($pct === null || $pct === '') {
            return FareFormulaService::DEFAULT_VENDOR_PERCENT;
        }

        return min(100.0, max(0.0, (float) $pct));
    }

    /**
     * Split a parcel fee.
     * Pool = commission % of the fee (default 5%).
     * Vendor = vendor % of that pool (default 10% of 5%) when a vendor registered it.
     * Admin = the rest of the pool.
     * Government levy = levy % of the fee (default 5%).
     * Bus owner = fee minus the pool minus the government levy.
     *
     * @return array{pool: float, vendor: float, admin: float, government: float, owner: float}
     */
    public static function splitAmounts(
        float $amountPaid,
        bool $hasVendor,
        float $commissionPercent = FareFormulaService::DEFAULT_COMMISSION_PERCENT,
        float $vendorPercentOfPool = FareFormulaService::DEFAULT_VENDOR_PERCENT,
        ?float $governmentPercent = null
    ): array {
        $amountPaid = max(0.0, $amountPaid);
        $governmentPercent = $governmentPercent ?? government_levy_percent();
        $pool = round($amountPaid * max(0.0, $commissionPercent) / 100, 2);
        $vendor = $hasVendor
            ? round($pool * min(100.0, max(0.0, $vendorPercentOfPool)) / 100, 2)
            : 0.0;
        $admin = round($pool - $vendor, 2);
        $government = round($amountPaid * max(0.0, $governmentPercent) / 100, 2);
        $owner = round(max(0.0, $amountPaid - $pool - $government), 2);

        return [
            'pool' => $pool,
            'vendor' => $vendor,
            'admin' => $admin,
            'government' => $government,
            'owner' => $owner,
        ];
    }

    /**
     * Split for an existing parcel: the amounts stored at settlement when present,
     * otherwise the current formula (unpaid parcels / previews).
     *
     * @return array{pool: float, vendor: float, admin: float, government: float, owner: float}
     */
    public static function splitForParcel($parcel): array
    {
        if (isset($parcel->owner_share) && $parcel->owner_share !== null) {
            $amount = max(0.0, round((float) ($parcel->amount_paid ?? 0), 2));
            $admin = round((float) ($parcel->admin_share ?? 0), 2);
            $vendor = round((float) ($parcel->vendor_share ?? 0), 2);
            $government = round((float) ($parcel->government_levy ?? 0), 2);
            if ($government <= 0 && $amount > 0 && strcasecmp((string) ($parcel->payment_status ?? ''), 'paid') === 0) {
                $government = government_levy_on_amount($amount);
            }
            $owner = round(max(0.0, $amount - $admin - $vendor - $government), 2);

            return [
                'pool' => round($admin + $vendor, 2),
                'vendor' => $vendor,
                'admin' => $admin,
                'government' => $government,
                'owner' => $owner,
            ];
        }

        $venderId = $parcel->vender_id ?? null;
        $hasVendor = !empty($venderId);

        return self::splitAmounts(
            (float) ($parcel->amount_paid ?? 0),
            $hasVendor,
            self::commissionPercent(),
            $hasVendor ? self::vendorPoolPercent($venderId) : 0.0
        );
    }

    /**
     * Bus-owner wallet share of a paid parcel (fee minus commission pool and government levy).
     */
    public static function ownerShareAmount(float $amountPaid, $venderId = null, ?float $systemPct = null, ?float $vendorPct = null): float
    {
        $hasVendor = !empty($venderId);
        $commission = $systemPct ?? self::commissionPercent();
        $vendorOfPool = $hasVendor
            ? ($vendorPct ?? self::vendorPoolPercent($venderId))
            : 0.0;

        return self::splitAmounts($amountPaid, $hasVendor, $commission, $vendorOfPool)['owner'];
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
     * Credit wallets for the FULL fee and mark the parcel fully paid. Idempotent.
     * Used by ClickPesa callbacks (existing behaviour), cash-at-registration and
     * the destination collection of an instalment balance / COD fee.
     */
    public function settleFully(Parcel $parcel, string $reference, string $method = 'cash'): Parcel
    {
        return DB::transaction(function () use ($parcel, $reference, $method) {
            $parcel = Parcel::whereKey($parcel->id)->lockForUpdate()->first();

            if ($parcel->payment_status === self::PAY_PAID && $parcel->settled_at) {
                return $parcel;
            }

            $amount = round((float) $parcel->amount_paid, 2);
            $hasVendor = !empty($parcel->vender_id);
            $split = self::splitAmounts(
                $amount,
                $hasVendor,
                self::commissionPercent(),
                $hasVendor ? self::vendorPoolPercent($parcel->vender_id) : 0.0
            );
            $systemShare = $split['admin'];
            $ownerShare = $split['owner'];
            $vendorShare = $split['vendor'];
            $governmentLevy = $split['government'];

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

            if ($campany && $governmentLevy > 0) {
                GovernmentLevy::create([
                    'campany_id' => $campany->id,
                    'booking_id' => $parcel->parcel_number,
                    'amount' => $governmentLevy,
                ]);
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

            $normalized = $this->normalizeStatus($parcel);
            $update = [
                'payment_status' => self::PAY_PAID,
                'payment_method' => $method,
                'payment_ref' => $reference,
                'settled_at' => now(),
                'paid_amount' => $amount,
                'balance_due' => 0,
                'balance_paid_at' => now(),
                'admin_share' => $systemShare,
                'vendor_share' => $vendorShare,
                'government_levy' => $governmentLevy,
                'owner_share' => $ownerShare,
            ];

            // Never regress a parcel that already moved past registration.
            if (in_array($normalized, [self::STATUS_AWAITING_PAYMENT, self::STATUS_PENDING], true)) {
                $update['status'] = self::STATUS_REGISTERED;
            }

            $parcel->update($update);

            Log::info('Parcel payment settled', [
                'parcel_id' => $parcel->id,
                'amount' => $amount,
                'system' => $systemShare,
                'owner' => $ownerShare,
                'vendor' => $vendorShare,
                'government_levy' => $governmentLevy,
                'reference' => $reference,
            ]);

            return $parcel->fresh(['bus.campany', 'bus.route']);
        });
    }

    /**
     * Backwards-compatible wrapper around settleFully() so existing ClickPesa
     * callers keep working unchanged.
     */
    public function confirmPayment(Parcel $parcel, string $reference, string $method = 'clickpesa'): Parcel
    {
        return $this->settleFully($parcel, $reference, $method);
    }

    /**
     * Reverse the wallet credits created when a parcel settled. Idempotent: the
     * `commission_reversed_at` marker is the single source of truth, so this can
     * be called repeatedly (including to repair parcels cancelled before the
     * marker existed) without reversing twice.
     *
     * Reverses only parcels that actually moved money: fully paid
     * (payment_status = paid) with a recorded settlement and no prior reversal.
     * Partial instalment deposits and COD parcels never credited wallets, so
     * they are ignored. payment_status is never changed.
     *
     * @return array{reversed: bool, owner: float, admin: float, vendor: float, government: float}
     */
    public function reverseSettledParcelIfNeeded(Parcel $parcel): array
    {
        return DB::transaction(function () use ($parcel) {
            $locked = Parcel::whereKey($parcel->id)->lockForUpdate()->first();

            $noop = [
                'reversed' => false,
                'owner' => 0.0,
                'admin' => 0.0,
                'vendor' => 0.0,
                'government' => 0.0,
            ];

            if (
                !$locked
                || $locked->payment_status !== self::PAY_PAID
                || $locked->settled_at === null
                || $locked->commission_reversed_at !== null
            ) {
                return $noop;
            }

            $split = self::splitForParcel($locked);
            $owner = (float) $split['owner'];
            $admin = (float) $split['admin'];
            $vendor = (float) $split['vendor'];
            $government = (float) $split['government'];

            $locked->loadMissing('bus.campany.balance');
            $campany = $locked->bus?->campany;

            if ($campany && $campany->balance && $owner > 0) {
                $campany->balance->decrement('amount', $owner);
            }

            if ($admin > 0) {
                $adminWallet = AdminWallet::find(1) ?: AdminWallet::query()->first();
                if ($adminWallet) {
                    $adminWallet->decrement('balance', $admin);
                }
            }

            if ($locked->vender_id && $vendor > 0) {
                $venderBalance = VenderBalance::where('user_id', $locked->vender_id)->first();
                if ($venderBalance) {
                    $venderBalance->decrement('amount', $vendor);
                }
            }

            GovernmentLevy::where('booking_id', $locked->parcel_number)->delete();

            $locked->update(['commission_reversed_at' => now()]);

            return [
                'reversed' => true,
                'owner' => $owner,
                'admin' => $admin,
                'vendor' => $vendor,
                'government' => $government,
            ];
        });
    }

    /**
     * Cancel a parcel and reverse the wallet credits made when it settled.
     *
     * Reversal only happens when the parcel actually moved money: fully paid
     * (payment_status = paid) with a recorded settlement and no prior reversal.
     * Partial instalment deposits and COD parcels never credited wallets, so
     * they are cancelled without touching any balance. payment_status is
     * deliberately left as `paid` so a cancelled parcel can never be
     * re-settled. Idempotent via reverseSettledParcelIfNeeded() and the
     * commission_reversed_at marker.
     */
    public function cancelWithReversal(Parcel $parcel): Parcel
    {
        return DB::transaction(function () use ($parcel) {
            $locked = Parcel::whereKey($parcel->id)->lockForUpdate()->first();

            $reversal = $this->reverseSettledParcelIfNeeded($locked);

            if ($locked->status !== self::STATUS_CANCELLED) {
                $locked->update(['status' => self::STATUS_CANCELLED]);
            }

            Log::info('Parcel cancelled with reversal', [
                'parcel_id' => $locked->id,
                'owner' => $reversal['owner'],
                'admin' => $reversal['admin'],
                'vendor' => $reversal['vendor'],
                'government' => $reversal['government'],
                'reversed' => $reversal['reversed'],
            ]);

            return $locked->fresh();
        });
    }

    /**
     * Record a deposit against the parcel fee (instalment mode). When the deposit
     * clears the fee the parcel settles fully and the wallets are credited;
     * otherwise it moves to `partial` with a balance still due at the destination.
     * The parcel is allowed to move in both cases.
     */
    public function applyDeposit(Parcel $parcel, float $amount, string $reference, string $method = 'clickpesa'): Parcel
    {
        $parcel = DB::transaction(function () use ($parcel, $amount, $reference, $method) {
            $locked = Parcel::whereKey($parcel->id)->lockForUpdate()->first();
            $amount = max(0.0, round($amount, 2));

            $total = round((float) ($locked->amount_paid ?? 0), 2);
            $paid = round((float) ($locked->paid_amount ?? 0) + $amount, 2);
            if ($paid > $total) {
                $paid = $total;
            }
            $balance = round(max(0.0, $total - $paid), 2);

            // Deposit clears the fee: settle like a full cash payment.
            if ($balance <= 0.01) {
                return $this->settleFully($locked, $reference, $method);
            }

            $normalized = $this->normalizeStatus($locked);
            $update = [
                'paid_amount' => $paid,
                'balance_due' => $balance,
                'payment_status' => self::PAY_PARTIAL,
                'payment_method' => $method,
                'payment_ref' => $reference,
                'deposit_paid_at' => now(),
            ];

            if (in_array($normalized, [self::STATUS_AWAITING_PAYMENT, self::STATUS_PENDING], true)) {
                $update['status'] = self::STATUS_REGISTERED;
            }

            $locked->update($update);

            return $locked->fresh(['bus.campany', 'bus.route']);
        });

        $this->notifyRegistered($parcel);

        return $parcel->fresh();
    }

    /** True when the parcel fee has been settled in full. */
    public function isFullyPaid(?Parcel $parcel): bool
    {
        if (!$parcel) {
            return false;
        }

        return ($parcel->payment_status ?? null) === self::PAY_PAID;
    }

    /**
     * True when the payment state allows the parcel to physically move.
     * Paid and partial parcels are fine; COD moves with nothing collected yet.
     * Only unpaid / pending / awaiting-payment parcels are blocked.
     */
    public function paymentAllowsMovement(?Parcel $parcel): bool
    {
        if (!$parcel) {
            return false;
        }

        return in_array($parcel->payment_status ?? null, [
            self::PAY_PAID,
            self::PAY_PARTIAL,
            self::PAY_COD,
        ], true);
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
        $payload = [];
        foreach ([
            'receiving_user_id',
            'receiving_agent_name',
            'receiving_agent_phone',
            'delivery_rider_name',
            'delivery_rider_phone',
            'bus_id',
        ] as $key) {
            if (array_key_exists($key, $data)) {
                $payload[$key] = $data[$key];
            }
        }

        if ($payload !== []) {
            $parcel->update($payload);
        }

        return $parcel->fresh();
    }

    /**
     * Origin office records that the parcel is kept in the store (not loaded yet).
     */
    public function markInStore(Parcel $parcel): Parcel
    {
        if ($this->normalizeStatus($parcel) !== self::STATUS_REGISTERED) {
            throw new \RuntimeException(__('vender/parcels.cannot_store'));
        }

        if (!$this->paymentAllowsMovement($parcel)) {
            throw new \RuntimeException(__('vender/parcels.cannot_receive_unpaid'));
        }

        $parcel->update(['status' => self::STATUS_IN_STORE]);

        $parcel = $parcel->fresh(['bus.campany', 'bus.route']);
        $this->notifyRegistered($parcel);

        return $parcel;
    }

    /**
     * Load onto the bus and capture the conductor who takes custody of it.
     */
    public function markLoaded(Parcel $parcel, string $conductorName, string $conductorPhone, ?User $actor = null): Parcel
    {
        $status = $this->normalizeStatus($parcel);
        if (!in_array($status, [self::STATUS_REGISTERED, self::STATUS_IN_STORE], true)) {
            throw new \RuntimeException(__('vender/parcels.cannot_load'));
        }

        if (!$this->paymentAllowsMovement($parcel)) {
            throw new \RuntimeException(__('vender/parcels.cannot_load_unpaid'));
        }

        $parcel->loadMissing('bus.campany', 'bus.route');

        $parcel->update([
            'status' => self::STATUS_LOADED,
            'loaded_at' => now(),
            'loaded_by_user_id' => $actor?->id,
            'conductor_name' => $conductorName,
            'conductor_phone' => $conductorPhone,
        ]);

        $parcel = $parcel->fresh(['bus.campany', 'bus.route']);

        $company = optional($parcel->bus->campany)->name ?? 'Highlink';
        $msg = "Mzigo {$parcel->parcel_number} umepakiwa kwenye basi ({$company}). Konda: {$conductorName}, simu: {$conductorPhone}.";
        $this->smsSafe($parcel->receiver_contact_1, $msg, $parcel->id);
        $this->smsSafe($parcel->sender_contact, $msg, $parcel->id);

        return $parcel;
    }

    /**
     * Destination office confirms the parcel has arrived and been received.
     */
    public function markReceived(Parcel $parcel, ?User $actor = null): Parcel
    {
        $status = $this->normalizeStatus($parcel);
        if (!in_array($status, [self::STATUS_LOADED, self::STATUS_IN_TRANSIT], true)) {
            throw new \RuntimeException(__('vender/parcels.cannot_arrive'));
        }

        $parcel->update([
            'status' => self::STATUS_RECEIVED,
            'received_at' => now(),
            'receiving_user_id' => $actor?->id,
        ]);

        $parcel = $parcel->fresh(['bus.campany', 'bus.route']);

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

    /**
     * Hand the parcel to the person who came to collect it, capturing their
     * name, phone and signature before closing the movement.
     *
     * @param  array{name?: ?string, phone?: ?string, signature?: ?string}  $collector
     */
    public function collect(Parcel $parcel, string $trackingNumber, ?User $actor = null, array $collector = []): Parcel
    {
        $status = $this->normalizeStatus($parcel);
        if (!in_array($status, [self::STATUS_RECEIVED, self::STATUS_ARRIVED], true)) {
            throw new \RuntimeException(__('vender/parcels.cannot_collect'));
        }

        $expected = preg_replace('/\s+/', '', strtoupper((string) $parcel->parcel_number));
        $given = preg_replace('/\s+/', '', strtoupper(trim($trackingNumber)));
        if ($expected !== $given) {
            throw new \RuntimeException(__('vender/parcels.tracking_mismatch'));
        }

        $collectorName = trim((string) ($collector['name'] ?? '')) ?: null;
        $collectorPhone = trim((string) ($collector['phone'] ?? '')) ?: null;

        $parcel->update([
            'status' => self::STATUS_COMPLETED,
            'collected_at' => now(),
            'collector_name' => $collectorName,
            'collector_phone' => $collectorPhone,
            'collector_signature' => $collector['signature'] ?? null,
            'collected_by_user_id' => $actor?->id,
        ]);

        $this->smsSafe(
            $parcel->sender_contact,
            "Mzigo {$parcel->parcel_number} umepokelewa na " . ($collectorName ?: $parcel->receiver_name) . ".",
            $parcel->id
        );

        return $parcel->fresh();
    }

    /**
     * Stages where the physical parcel is no longer with the vendor: it has been
     * handed over and is in the bus owner's custody. Legacy aliases are mapped so
     * un-migrated rows behave the same.
     *
     * @return list<string>
     */
    public static function custodyStages(): array
    {
        return [
            self::STATUS_IN_STORE,
            self::STATUS_LOADED,
            self::STATUS_RECEIVED,
            self::STATUS_IN_TRANSIT, // legacy alias for loaded
            self::STATUS_ARRIVED,    // legacy alias for received
        ];
    }

    /**
     * True when the parcel has been handed over and is in the bus owner's custody.
     */
    public function inCustody(?string $status): bool
    {
        return in_array($status ?: self::STATUS_PENDING, self::custodyStages(), true);
    }

    /**
     * Who may cancel a parcel in a given stage.
     * Rule: once the parcel has been handed over (in the bus owner's custody),
     * only the bus owner manages its lifecycle — the vendor can no longer cancel.
     * Nobody may cancel an already completed parcel.
     */
    public function actorMayCancel(?string $status, bool $isBusOwner): bool
    {
        $status = $status ?: self::STATUS_PENDING;

        if ($status === self::STATUS_COMPLETED) {
            return false;
        }

        if ($isBusOwner) {
            return true;
        }

        return !$this->inCustody($status);
    }

    public function statusLabel(?string $status): string
    {
        $status = $status ?: 'pending';
        $key = 'vender/parcels.status_' . $status;
        $t = __($key);

        return $t === $key ? ucfirst(str_replace('_', ' ', $status)) : $t;
    }

    /** Human label for payment_status (paid / partial / cod / unpaid / pending). */
    public function paymentStatusLabel(?string $status): string
    {
        $status = $status ?: self::PAY_UNPAID;
        $key = 'vender/parcels.paystatus_' . $status;
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
     * Receipt print is allowed once the parcel has left the payment-awaiting
     * stages. Cash still requires full payment; instalment is printable with a
     * recorded deposit (the balance is shown on the receipt) and COD is always
     * printable because the fee is collected at the destination.
     */
    public function canPrintReceipt(?Parcel $parcel): bool
    {
        if (!$parcel) {
            return false;
        }

        $status = $this->normalizeStatus($parcel);
        if (in_array($status, [
            self::STATUS_AWAITING_PAYMENT,
            self::STATUS_PENDING,
            self::STATUS_CANCELLED,
        ], true)) {
            return false;
        }

        $mode = $parcel->payment_mode ?? self::MODE_CASH;

        if ($mode === self::MODE_COD) {
            return true;
        }

        if ($mode === self::MODE_INSTALMENT) {
            return $this->isFullyPaid($parcel) || (float) ($parcel->paid_amount ?? 0) > 0;
        }

        return $this->isPaymentConfirmed($parcel);
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
