<?php

namespace App\Services;

use App\Models\AdminWallet;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Operational lifecycle for excess luggage after ticket sale:
 * declared → weighed → awaiting_payment|ready → assigned → retrieved
 *
 * Extra collect (positive luggage_refund_amount) settles via ClickPesa;
 * refunds (negative) are recorded as a manual note only.
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
     * Record weigh-in measurements and fee reconciliation.
     * Positive delta → awaiting ClickPesa; otherwise ready for bus assignment.
     */
    public function weighIn(Booking $booking, array $data, User $actor): Booking
    {
        $fee = (float) ($data['excess_luggage_fee'] ?? $booking->excess_luggage_fee ?? 0);
        $verdict = $data['luggage_weight_verdict'] ?? null;
        $delta = isset($data['luggage_refund_amount']) && $data['luggage_refund_amount'] !== ''
            ? (float) $data['luggage_refund_amount']
            : null;

        // Normalize refund/payment amount sign to match weighmaster verdict.
        if ($verdict === 'correct') {
            $delta = 0.0;
        } elseif ($verdict === 'underestimated' && $delta !== null) {
            $delta = abs($delta);
        } elseif ($verdict === 'overestimated' && $delta !== null) {
            $delta = -abs($delta);
        }

        $status = self::STATUS_READY;
        $paymentStatus = self::PAYMENT_NONE;

        if ($delta !== null && $delta > 0) {
            $status = self::STATUS_AWAITING_PAYMENT;
            $paymentStatus = self::PAYMENT_PENDING;
        } elseif ($delta !== null && $delta < 0) {
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
            'luggage_weighed_at' => now(),
            'luggage_weighed_by' => $actor->id,
            // Clear prior assignment/retrieval if re-weighing
            'luggage_assigned_at' => null,
            'luggage_assigned_by' => null,
            'luggage_retrieved_at' => null,
            'luggage_retrieved_by' => null,
        ]);

        // Intermediate "weighed" is implied; persist explicit weighed when awaiting payment
        if ($status === self::STATUS_AWAITING_PAYMENT) {
            // Keep awaiting_payment as the visible status
        } else {
            // Was weighed then immediately ready — still ok
        }

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

    public function buildPaymentReference(Booking $booking): string
    {
        $code = preg_replace('/[^A-Za-z0-9]/', '', (string) $booking->booking_code) ?: 'BK';

        return $code . 'XLUG' . time();
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
     * Credit system + bus-owner shares for the extra (positive) luggage amount, then mark ready.
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

            $systemShare = round($extra * system_luggage_percent() / 100, 2);
            $ownerShare = round($extra - $systemShare, 2);

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

            $bus = $booking->bus()->with('campany.balance')->first();
            if ($bus && $bus->campany && $bus->campany->balance && $ownerShare > 0) {
                $bus->campany->balance->increment('amount', $ownerShare);
            } elseif ($ownerShare > 0) {
                Log::warning('Excess luggage top-up: bus owner balance missing', [
                    'booking_id' => $booking->id,
                    'owner_share' => $ownerShare,
                ]);
            }

            $booking->update([
                'excess_luggage_fee' => (float) ($booking->excess_luggage_fee ?? 0) + $extra,
                'luggage_payment_status' => self::PAYMENT_PAID,
                'luggage_status' => self::STATUS_READY,
                'luggage_payment_ref' => $reference,
            ]);

            Log::info('Excess luggage top-up settled', [
                'booking_id' => $booking->id,
                'extra' => $extra,
                'system_share' => $systemShare,
                'owner_share' => $ownerShare,
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
