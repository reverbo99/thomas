<?php

namespace App\Services;

use App\Models\AdminWallet;
use App\Models\Booking;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Operational lifecycle for excess luggage after ticket sale:
 * declared → weighed → awaiting_payment|ready → assigned → retrieved
 *
 * Extra collect (positive luggage_refund_amount) settles via ClickPesa;
 * refunds (negative) are requested by staff and approved by system admin
 * via luggage_payment_status (refund_noted → refund_pending → refunded).
 */
class ExcessLuggageService
{
    public const STATUS_DECLARED = 'declared';
    public const STATUS_WEIGHED = 'weighed';
    public const STATUS_AWAITING_PAYMENT = 'awaiting_payment';
    public const STATUS_READY = 'ready';
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_RETRIEVED = 'retrieved';

    public const PAYMENT_NONE = 'none_required';
    public const PAYMENT_PENDING = 'pending';
    public const PAYMENT_PAID = 'paid';
    public const PAYMENT_REFUND_NOTED = 'refund_noted';
    public const PAYMENT_REFUND_PENDING = 'refund_pending';
    public const PAYMENT_REFUNDED = 'refunded';
    public const PAYMENT_REFUND_REJECTED = 'refund_rejected';

    /** Company receipt / exit QR suffix — payload is `{booking_code}|XLUG`. */
    public const COMPANY_QR_SUFFIX = 'XLUG';

    /** Default TZS/kg when settings.excess_luggage_fee_per_kg is unset or 0. */
    public const DEFAULT_FEE_PER_KG = 2500.0;

    public function normalizeStatus(?Booking $booking): ?string
    {
        if (!$booking) {
            return null;
        }

        if (!empty($booking->luggage_status)) {
            return $booking->luggage_status;
        }

        if ((int) ($booking->has_excess_luggage ?? 0) === 1
            || (float) ($booking->excess_luggage_fee ?? 0) > 0) {
            return self::STATUS_DECLARED;
        }

        return null;
    }

    public function markDeclared(Booking $booking): void
    {
        if ((int) ($booking->has_excess_luggage ?? 0) !== 1
            && (float) ($booking->excess_luggage_fee ?? 0) <= 0) {
            return;
        }

        if (empty($booking->luggage_status)) {
            $booking->forceFill(['luggage_status' => self::STATUS_DECLARED])->save();
        }
    }

    /**
     * Platform rate used for weight-based excess luggage (TZS / kg).
     * Uses settings.excess_luggage_fee_per_kg; falls back to DEFAULT_FEE_PER_KG (2500) when unset/0.
     * Weigh-in reconciliation and booking fee share this same effective rate.
     */
    public function feePerKg(?Setting $settings = null): float
    {
        $settings = $settings ?: Setting::query()->first();
        $rate = round((float) ($settings->excess_luggage_fee_per_kg ?? 0), 2);

        return $rate > 0 ? $rate : self::DEFAULT_FEE_PER_KG;
    }

    /**
     * Booking-time excess luggage fee (TZS).
     * fee = max(0, estimated_weight) × fee_per_kg
     * fee_per_kg from settings.excess_luggage_fee_per_kg; fallback 2500 if unset/0.
     */
    public function computeBookingFee(bool $hasExcessLuggage, $estimatedWeight, ?Setting $settings = null): float
    {
        if (!$hasExcessLuggage) {
            return 0.0;
        }

        $weight = (float) $estimatedWeight;
        if ($weight <= 0) {
            return 0.0;
        }

        return round($weight * $this->feePerKg($settings), 2);
    }

    /**
     * Compute weigh-in verdict + refund/top-up delta from actual vs estimated weight.
     *
     * Booking fee is weight × rate (see computeBookingFee). Weigh-in reconciles paid fee
     * against actual weight using the same rate when available.
     *
     * Formula (weight-based, preferred when settings.excess_luggage_fee_per_kg > 0):
     *   delta = round((actual_weight - estimated_weight) × fee_per_kg, 2)
     * When estimated_weight is missing:
     *   delta = round(actual_weight × fee_per_kg - paid_fee, 2)
     * Fallback when fee_per_kg is 0 but estimated_weight > 0 (proportional to paid fee):
     *   delta = round(paid_fee × (actual_weight - estimated_weight) / estimated_weight, 2)
     *
     * Dimensions are stored for the receipt but are not part of the fee formula
     * (no volumetric charge exists in this system).
     *
     * @return array{delta: float, verdict: string, fee_per_kg: float, actual_weight: ?float, estimated_weight: ?float, paid_fee: float}
     */
    public function computeWeighInReconciliation(
        Booking $booking,
        $actualWeight,
        $paidFee = null,
        $estimatedWeight = null,
        ?Setting $settings = null
    ): array {
        $feePerKg = $this->feePerKg($settings);
        $paid = $paidFee !== null && $paidFee !== ''
            ? (float) $paidFee
            : (float) ($booking->excess_luggage_fee ?? 0);
        $estimated = $estimatedWeight !== null && $estimatedWeight !== ''
            ? (float) $estimatedWeight
            : ($booking->estimated_weight !== null ? (float) $booking->estimated_weight : null);
        $actual = ($actualWeight !== null && $actualWeight !== '')
            ? (float) $actualWeight
            : null;

        $delta = 0.0;

        if ($actual !== null) {
            if ($feePerKg > 0) {
                if ($estimated !== null) {
                    $delta = round(($actual - $estimated) * $feePerKg, 2);
                } else {
                    $delta = round(($actual * $feePerKg) - $paid, 2);
                }
            } elseif ($estimated !== null && $estimated > 0 && $paid > 0) {
                $delta = round($paid * (($actual - $estimated) / $estimated), 2);
            }
        }

        if (abs($delta) < 0.005) {
            $delta = 0.0;
        }

        if ($delta > 0) {
            $verdict = 'underestimated';
        } elseif ($delta < 0) {
            $verdict = 'overestimated';
        } else {
            $verdict = 'correct';
        }

        return [
            'delta' => $delta,
            'verdict' => $verdict,
            'fee_per_kg' => $feePerKg,
            'actual_weight' => $actual,
            'estimated_weight' => $estimated,
            'paid_fee' => $paid,
        ];
    }

    /**
     * Record weigh-in measurements and fee reconciliation.
     * Verdict + luggage_refund_amount are computed automatically from actual vs estimated weight.
     * Positive delta → awaiting ClickPesa; negative → refund_noted (staff may request admin refund); zero → ready.
     */
    public function weighIn(Booking $booking, array $data, User $actor): Booking
    {
        $fee = (float) ($data['excess_luggage_fee'] ?? $booking->excess_luggage_fee ?? 0);

        $calc = $this->computeWeighInReconciliation(
            $booking,
            $data['actual_weight'] ?? null,
            $fee
        );

        $delta = $calc['delta'];
        $verdict = $calc['verdict'];

        $status = self::STATUS_READY;
        $paymentStatus = self::PAYMENT_NONE;

        if ($delta > 0) {
            $status = self::STATUS_AWAITING_PAYMENT;
            $paymentStatus = self::PAYMENT_PENDING;
        } elseif ($delta < 0) {
            $paymentStatus = self::PAYMENT_REFUND_NOTED;
        }

        $booking->update([
            'has_excess_luggage' => 1,
            'excess_luggage_fee' => $fee,
            'excess_luggage_description' => $data['excess_luggage_description'] ?? $booking->excess_luggage_description,
            'actual_weight' => $data['actual_weight'] ?? null,
            'actual_length' => $data['actual_length'] ?? null,
            'actual_height' => $data['actual_height'] ?? null,
            'actual_width' => $data['actual_width'] ?? null,
            'luggage_refund_amount' => $delta,
            'luggage_weight_verdict' => $verdict,
            'luggage_status' => $status === self::STATUS_AWAITING_PAYMENT
                ? self::STATUS_AWAITING_PAYMENT
                : self::STATUS_READY,
            'luggage_payment_status' => $paymentStatus,
            'luggage_payment_ref' => null,
            'luggage_weighed_at' => now(),
            'luggage_weighed_by' => $actor->id,
            // Clear prior assignment/retrieval if re-weighing
            'luggage_assigned_at' => null,
            'luggage_assigned_by' => null,
            'luggage_retrieved_at' => null,
            'luggage_retrieved_by' => null,
        ]);

        return $booking->fresh();
    }

    public function clear(Booking $booking): Booking
    {
        $booking->update([
            'has_excess_luggage' => 0,
            'excess_luggage_fee' => 0,
            'excess_luggage_description' => null,
            'actual_weight' => null,
            'actual_length' => null,
            'actual_height' => null,
            'actual_width' => null,
            'luggage_refund_amount' => null,
            'luggage_weight_verdict' => null,
            'luggage_status' => null,
            'luggage_payment_ref' => null,
            'luggage_payment_status' => null,
            'luggage_weighed_at' => null,
            'luggage_weighed_by' => null,
            'luggage_assigned_at' => null,
            'luggage_assigned_by' => null,
            'luggage_retrieved_at' => null,
            'luggage_retrieved_by' => null,
        ]);

        return $booking->fresh();
    }

    public function amountDue(Booking $booking): float
    {
        $delta = (float) ($booking->luggage_refund_amount ?? 0);
        if ($delta <= 0) {
            return 0.0;
        }
        if ($booking->luggage_payment_status === self::PAYMENT_PAID) {
            return 0.0;
        }

        return $delta;
    }

    /**
     * Absolute refund owed to the passenger when weigh-in found overpayment
     * (negative luggage_refund_amount). Zero once admin has approved the refund.
     */
    public function refundAmount(Booking $booking): float
    {
        $delta = (float) ($booking->luggage_refund_amount ?? 0);
        if ($delta >= 0) {
            return 0.0;
        }
        if ($booking->luggage_payment_status === self::PAYMENT_REFUNDED) {
            return 0.0;
        }

        return round(abs($delta), 2);
    }

    public function canRequestRefund(Booking $booking): bool
    {
        if ((float) ($booking->luggage_refund_amount ?? 0) >= 0) {
            return false;
        }
        if ($booking->luggage_payment_status === self::PAYMENT_REFUNDED) {
            return false;
        }
        if ($booking->luggage_payment_status === self::PAYMENT_REFUND_PENDING) {
            return false;
        }

        return in_array($booking->luggage_payment_status, [
            self::PAYMENT_REFUND_NOTED,
            self::PAYMENT_REFUND_REJECTED,
            self::PAYMENT_NONE,
            null,
            '',
        ], true);
    }

    public function hasPendingRefundRequest(Booking $booking): bool
    {
        return $booking->luggage_payment_status === self::PAYMENT_REFUND_PENDING
            && (float) ($booking->luggage_refund_amount ?? 0) < 0;
    }

    /**
     * Staff submits a luggage overpayment refund for system-admin approval.
     * Stores a short ref on luggage_payment_ref (XLUGREF…).
     */
    public function requestRefund(Booking $booking, User $actor, array $data = []): Booking
    {
        $amount = $this->refundAmount($booking);
        if ($amount <= 0) {
            throw new \RuntimeException(__('vender/luggage.no_refund_due'));
        }

        if ($booking->luggage_payment_status === self::PAYMENT_REFUND_PENDING) {
            throw new \RuntimeException(__('vender/luggage.refund_already_pending'));
        }

        if ($booking->luggage_payment_status === self::PAYMENT_REFUNDED) {
            throw new \RuntimeException(__('vender/luggage.refund_already_processed'));
        }

        if (!$this->canRequestRefund($booking)) {
            throw new \RuntimeException(__('vender/luggage.no_refund_due'));
        }

        $phone = trim((string) ($data['phone'] ?? $booking->customer_phone ?? ''));
        $name = trim((string) ($data['fullname'] ?? $booking->customer_name ?? ''));
        $ref = $this->buildRefundRequestReference($booking);

        $currentStatus = $this->normalizeStatus($booking);
        $booking->update([
            'luggage_payment_status' => self::PAYMENT_REFUND_PENDING,
            'luggage_payment_ref' => $ref,
            // Keep luggage operational status so assign/reclaim stay available.
            'luggage_status' => in_array($currentStatus, [
                self::STATUS_ASSIGNED,
                self::STATUS_RETRIEVED,
            ], true)
                ? $booking->luggage_status
                : self::STATUS_READY,
        ]);

        Log::info('Excess luggage refund requested', [
            'booking_id' => $booking->id,
            'amount' => $amount,
            'reference' => $ref,
            'phone' => $phone,
            'fullname' => $name,
            'actor_id' => $actor->id,
        ]);

        return $booking->fresh();
    }

    public function buildRefundRequestReference(Booking $booking): string
    {
        $code = preg_replace('/[^A-Za-z0-9]/', '', (string) $booking->booking_code) ?: 'BK';
        $shortCode = substr($code, 0, 6);
        $shortTime = substr((string) time(), -6);

        return $shortCode . 'XLUGREF' . $shortTime;
    }

    /**
     * System admin approves a pending luggage refund: reverse fee shares for |delta|,
     * reduce excess_luggage_fee, mark payment status refunded.
     */
    public function approveRefund(Booking $booking, User $actor): Booking
    {
        return DB::transaction(function () use ($booking, $actor) {
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->first();

            if ($booking->luggage_payment_status === self::PAYMENT_REFUNDED) {
                return $booking;
            }

            if ($booking->luggage_payment_status !== self::PAYMENT_REFUND_PENDING) {
                throw new \RuntimeException(__('vender/luggage.refund_not_pending'));
            }

            $refund = $this->refundAmount($booking);
            if ($refund <= 0) {
                throw new \RuntimeException(__('vender/luggage.no_refund_due'));
            }

            $booking->loadMissing(['bus.campany.balance']);
            $split = split_luggage_fee_amount($refund);
            $systemShare = $split['system'];
            $governmentShare = $split['government'];
            $ownerShare = $split['owner'];

            $adminWallet = AdminWallet::find(1) ?: AdminWallet::query()->first();
            if ($adminWallet && $systemShare > 0) {
                $adminWallet->decrement('balance', min((float) $adminWallet->balance, $systemShare));
            }

            $bus = $booking->bus;
            if ($governmentShare > 0 && $bus && $bus->campany) {
                \App\Models\GovernmentLevy::create([
                    'campany_id' => $bus->campany->id,
                    'booking_id' => $booking->booking_code,
                    'amount' => -1 * $governmentShare,
                ]);
            }

            if ($bus && $bus->campany && $bus->campany->balance && $ownerShare > 0) {
                $bus->campany->balance->decrement(
                    'amount',
                    min((float) $bus->campany->balance->amount, $ownerShare)
                );
            } elseif ($ownerShare > 0) {
                Log::warning('Excess luggage refund: bus owner balance missing', [
                    'booking_id' => $booking->id,
                    'owner_share' => $ownerShare,
                ]);
            }

            $newFee = max(0.0, round((float) ($booking->excess_luggage_fee ?? 0) - $refund, 2));
            $payload = [
                'excess_luggage_fee' => $newFee,
                'luggage_payment_status' => self::PAYMENT_REFUNDED,
            ];
            if ($booking->customer_paid_total !== null) {
                $payload['customer_paid_total'] = max(
                    0.0,
                    round((float) $booking->customer_paid_total - $refund, 2)
                );
            }

            $booking->update($payload);

            Log::info('Excess luggage refund approved', [
                'booking_id' => $booking->id,
                'refund' => $refund,
                'system_share' => $systemShare,
                'government_share' => $governmentShare,
                'owner_share' => $ownerShare,
                'actor_id' => $actor->id,
                'reference' => $booking->luggage_payment_ref,
            ]);

            return $booking->fresh();
        });
    }

    public function rejectRefund(Booking $booking, User $actor): Booking
    {
        if ($booking->luggage_payment_status !== self::PAYMENT_REFUND_PENDING) {
            throw new \RuntimeException(__('vender/luggage.refund_not_pending'));
        }

        $booking->update([
            'luggage_payment_status' => self::PAYMENT_REFUND_REJECTED,
        ]);

        Log::info('Excess luggage refund rejected', [
            'booking_id' => $booking->id,
            'actor_id' => $actor->id,
            'reference' => $booking->luggage_payment_ref,
        ]);

        return $booking->fresh();
    }

    public function paymentStatusLabel(?string $status): string
    {
        $status = $status ?: 'none';
        $key = 'vender/luggage.payment_' . $status;
        $translated = __($key);

        return $translated === $key ? ucfirst(str_replace('_', ' ', $status)) : $translated;
    }

    /**
     * ClickPesa order reference — must be ≤ 20 alphanumeric characters.
     * Format: {shortCode}XLUG{6-digit time} (max 6+4+6 = 16).
     */
    public function buildPaymentReference(Booking $booking): string
    {
        $code = preg_replace('/[^A-Za-z0-9]/', '', (string) $booking->booking_code) ?: 'BK';
        $shortCode = substr($code, 0, 6);
        $shortTime = substr((string) time(), -6);

        return $shortCode . 'XLUG' . $shortTime;
    }

    public function findByPaymentReference(string $reference): ?Booking
    {
        $sanitized = preg_replace('/[^a-zA-Z0-9]/', '', $reference) ?: $reference;

        $booking = Booking::where(function ($q) use ($sanitized, $reference) {
            $q->where('luggage_payment_ref', $sanitized)
                ->orWhere('luggage_payment_ref', $reference);
        })->first();

        if ($booking) {
            return $booking;
        }

        // Fallback: parse booking_code prefix before XLUG
        if (preg_match('/^(.+?)XLUG\d+$/i', $sanitized, $m)) {
            $prefix = $m[1];
            return Booking::whereRaw(
                "REPLACE(REPLACE(booking_code, '-', ''), '_', '') LIKE ?",
                [$prefix . '%']
            )->where(function ($q) {
                $q->where('luggage_payment_status', self::PAYMENT_PENDING)
                    ->orWhere('luggage_status', self::STATUS_AWAITING_PAYMENT);
            })->orderByDesc('id')->first();
        }

        return null;
    }

    /**
     * Credit system / government / bus-owner shares for the extra (positive) luggage amount,
     * sync customer_paid_total for GMV, then mark ready.
     *
     * Split: admin 5% + government 5% + bus owner 90% (split_luggage_fee_amount).
     */
    public function confirmTopUpPayment(Booking $booking, string $reference): Booking
    {
        return DB::transaction(function () use ($booking, $reference) {
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->first();

            if ($booking->luggage_payment_status === self::PAYMENT_PAID
                && $booking->luggage_status === self::STATUS_READY) {
                return $booking;
            }

            $extra = (float) ($booking->luggage_refund_amount ?? 0);
            if ($extra <= 0) {
                $booking->update([
                    'luggage_payment_status' => self::PAYMENT_NONE,
                    'luggage_status' => self::STATUS_READY,
                    'luggage_payment_ref' => $reference,
                ]);

                return $booking->fresh();
            }

            $booking->loadMissing(['vender.VenderAccount', 'vender.VenderBalances', 'bus.campany.balance']);
            $split = split_luggage_fee_amount($extra);
            $systemShare = $split['system'];
            $governmentShare = $split['government'];
            $ownerShare = $split['owner'];

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

            $bus = $booking->bus;
            if ($governmentShare > 0 && $bus && $bus->campany) {
                \App\Models\GovernmentLevy::create([
                    'campany_id' => $bus->campany->id,
                    'booking_id' => $booking->booking_code,
                    'amount' => $governmentShare,
                ]);
            }

            if ($bus && $bus->campany && $bus->campany->balance && $ownerShare > 0) {
                $bus->campany->balance->increment('amount', $ownerShare);
            } elseif ($ownerShare > 0) {
                Log::warning('Excess luggage top-up: bus owner balance missing', [
                    'booking_id' => $booking->id,
                    'owner_share' => $ownerShare,
                ]);
            }

            // Fold top-up into GMV base without a second luggage addend in dashboard sums.
            // Only when customer_paid_total is already set (post-settlement bookings) so we
            // never replace COALESCE(customer_paid_total, amount) with a bare top-up figure.
            $payload = [
                'excess_luggage_fee' => (float) ($booking->excess_luggage_fee ?? 0) + $extra,
                'luggage_payment_status' => self::PAYMENT_PAID,
                'luggage_status' => self::STATUS_READY,
                'luggage_payment_ref' => $reference,
            ];
            if ($booking->customer_paid_total !== null) {
                $payload['customer_paid_total'] = (float) $booking->customer_paid_total + $extra;
            }

            $booking->update($payload);

            Log::info('Excess luggage top-up settled', [
                'booking_id' => $booking->id,
                'extra' => $extra,
                'system_share' => $systemShare,
                'government_share' => $governmentShare,
                'owner_share' => $ownerShare,
                'customer_paid_total' => $payload['customer_paid_total'] ?? null,
                'reference' => $reference,
            ]);

            return $booking->fresh();
        });
    }

    public function assignToBus(Booking $booking, User $actor): Booking
    {
        if ($this->amountDue($booking) > 0) {
            throw new \RuntimeException(__('vender/luggage.assign_payment_pending'));
        }

        $status = $this->normalizeStatus($booking);
        if ($status === self::STATUS_ASSIGNED) {
            return $booking;
        }

        if (!in_array($status, [self::STATUS_READY, self::STATUS_WEIGHED], true)) {
            throw new \RuntimeException(__('vender/luggage.assign_not_ready'));
        }

        $booking->update([
            'luggage_status' => self::STATUS_ASSIGNED,
            'luggage_assigned_at' => now(),
            'luggage_assigned_by' => $actor->id,
        ]);

        return $booking->fresh();
    }

    public function reclaim(Booking $booking, User $actor): Booking
    {
        $status = $this->normalizeStatus($booking);

        if ($status === self::STATUS_RETRIEVED) {
            return $booking;
        }

        if (!in_array($status, [self::STATUS_ASSIGNED, self::STATUS_READY], true)) {
            throw new \RuntimeException(__('vender/luggage.reclaim_not_assigned'));
        }

        $booking->update([
            'luggage_status' => self::STATUS_RETRIEVED,
            'luggage_retrieved_at' => now(),
            'luggage_retrieved_by' => $actor->id,
        ]);

        return $booking->fresh();
    }

    /**
     * Company QR on the excess luggage receipt (exit / reclaim at arrival).
     * Format mirrors ticket seat QR: `{booking_code}|XLUG`.
     */
    public function buildCompanyQrPayload(Booking $booking): string
    {
        $code = trim((string) ($booking->booking_code ?? ''));

        return $code . '|' . self::COMPANY_QR_SUFFIX;
    }

    /**
     * Parse a scanned company luggage QR. Returns booking_code or null if invalid.
     */
    public function parseCompanyQrPayload(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        if (preg_match('/^(.+?)\|' . preg_quote(self::COMPANY_QR_SUFFIX, '/') . '$/i', $raw, $m)) {
            $code = trim($m[1]);

            return $code !== '' ? $code : null;
        }

        return null;
    }

    public function matchesCompanyQr(Booking $booking, string $raw): bool
    {
        $code = $this->parseCompanyQrPayload($raw);
        if ($code === null) {
            return false;
        }

        return strcasecmp($code, (string) $booking->booking_code) === 0;
    }

    public function findByCompanyQr(string $raw): ?Booking
    {
        $code = $this->parseCompanyQrPayload($raw);
        if ($code === null) {
            return null;
        }

        return Booking::query()->where('booking_code', $code)->first();
    }

    public function statusLabel(?string $status): string
    {
        $status = $status ?: 'none';
        $key = 'vender/luggage.status_' . $status;

        $translated = __($key);

        return $translated === $key ? ucfirst(str_replace('_', ' ', $status)) : $translated;
    }

    /**
     * Receipt print allowed only when ticket is paid and no top-up is still owed.
     * Blocks: unpaid ticket, awaiting_payment status, pending luggage_payment_status, or amountDue > 0.
     */
    public function canPrintReceipt(?Booking $booking): bool
    {
        if (!$booking) {
            return false;
        }

        $hasLuggage = (int) ($booking->has_excess_luggage ?? 0) === 1
            || (float) ($booking->excess_luggage_fee ?? 0) > 0
            || !empty($booking->luggage_status);

        if (!$hasLuggage) {
            return false;
        }

        if (($booking->payment_status ?? '') !== 'Paid') {
            return false;
        }

        if ($this->amountDue($booking) > 0) {
            return false;
        }

        if (($booking->luggage_payment_status ?? null) === self::PAYMENT_PENDING) {
            return false;
        }

        if ($this->normalizeStatus($booking) === self::STATUS_AWAITING_PAYMENT) {
            return false;
        }

        return true;
    }
}
