<?php

use App\Models\Booking;
use App\Models\ExcessLuggageEscrow;
use App\Models\ExcessLuggageEscrowTransaction;
use App\Services\ExcessLuggageService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('excess_luggage_escrow')) {
            return;
        }

        Booking::query()
            ->where('payment_status', 'Paid')
            ->where(function ($q) {
                $q->where('excess_luggage_fee', '>', 0)
                    ->orWhere('has_excess_luggage', 1);
            })
            ->whereNotIn('id', ExcessLuggageEscrow::query()->pluck('booking_id'))
            ->orderBy('id')
            ->chunkById(200, function ($bookings) {
                foreach ($bookings as $booking) {
                    $this->backfillBooking($booking);
                }
            });
    }

    private function backfillBooking(Booking $booking): void
    {
        $held = (float) ($booking->excess_luggage_fee ?? 0);
        if ($held <= 0) {
            return;
        }

        $weighed = !empty($booking->luggage_weighed_at);
        $releasedFee = $held;

        if ($weighed && $booking->actual_weight !== null) {
            $svc = app(ExcessLuggageService::class);
            $calc = $svc->computeWeighInReconciliation(
                $booking,
                $booking->actual_weight,
                $held
            );
            if ($calc['actual_weight'] !== null && $calc['fee_per_kg'] > 0) {
                $releasedFee = round((float) $calc['actual_weight'] * (float) $calc['fee_per_kg'], 2);
            }
        }

        $surplus = max(0.0, round($held - $releasedFee, 2));
        $split = split_luggage_fee_amount($releasedFee);

        $status = ExcessLuggageEscrow::STATUS_RELEASED;
        if ($surplus > 0.005) {
            $status = ExcessLuggageEscrow::STATUS_SURPLUS_HELD;
        }
        if (($booking->luggage_payment_status ?? '') === 'refunded') {
            $status = ExcessLuggageEscrow::STATUS_REFUNDED;
        } elseif (($booking->luggage_payment_status ?? '') === 'refund_pending') {
            $status = ExcessLuggageEscrow::STATUS_REFUND_PENDING;
        } elseif (($booking->luggage_status ?? '') === 'awaiting_payment') {
            $status = ExcessLuggageEscrow::STATUS_AWAITING_TOPUP;
        } elseif (!$weighed) {
            $status = ExcessLuggageEscrow::STATUS_RELEASED;
            $releasedFee = $held;
            $surplus = 0;
            $split = split_luggage_fee_amount($releasedFee);
        }

        DB::transaction(function () use ($booking, $held, $releasedFee, $surplus, $split, $status, $weighed) {
            $escrow = ExcessLuggageEscrow::create([
                'booking_id' => $booking->id,
                'booking_code' => $booking->booking_code,
                'estimated_weight' => $booking->estimated_weight,
                'estimated_fee' => $held,
                'held_amount' => $held,
                'actual_weight' => $weighed ? $booking->actual_weight : null,
                'actual_fee' => $weighed ? $releasedFee : null,
                'delta_amount' => $weighed ? $booking->luggage_refund_amount : null,
                'weight_verdict' => $weighed ? $booking->luggage_weight_verdict : null,
                'released_fee' => in_array($status, [
                    ExcessLuggageEscrow::STATUS_RELEASED,
                    ExcessLuggageEscrow::STATUS_SURPLUS_HELD,
                    ExcessLuggageEscrow::STATUS_REFUNDED,
                ], true) ? $releasedFee : null,
                'surplus_amount' => $surplus,
                'admin_share' => in_array($status, [
                    ExcessLuggageEscrow::STATUS_RELEASED,
                    ExcessLuggageEscrow::STATUS_SURPLUS_HELD,
                    ExcessLuggageEscrow::STATUS_REFUNDED,
                ], true) ? $split['system'] : 0,
                'government_share' => in_array($status, [
                    ExcessLuggageEscrow::STATUS_RELEASED,
                    ExcessLuggageEscrow::STATUS_SURPLUS_HELD,
                    ExcessLuggageEscrow::STATUS_REFUNDED,
                ], true) ? $split['government'] : 0,
                'owner_share' => 0,
                'status' => $status,
                'refund_amount' => ($booking->luggage_payment_status ?? '') === 'refunded'
                    ? $surplus
                    : null,
                'weighed_at' => $weighed ? $booking->luggage_weighed_at : null,
                'released_at' => in_array($status, [
                    ExcessLuggageEscrow::STATUS_RELEASED,
                    ExcessLuggageEscrow::STATUS_SURPLUS_HELD,
                    ExcessLuggageEscrow::STATUS_REFUNDED,
                ], true) ? ($booking->luggage_weighed_at ?? $booking->updated_at) : null,
                'refund_requested_at' => ($booking->luggage_payment_status ?? '') === 'refund_pending'
                    ? now()
                    : null,
                'refund_approved_at' => ($booking->luggage_payment_status ?? '') === 'refunded'
                    ? now()
                    : null,
            ]);

            ExcessLuggageEscrowTransaction::create([
                'escrow_id' => $escrow->id,
                'booking_id' => $booking->id,
                'type' => ExcessLuggageEscrowTransaction::TYPE_DEPOSIT,
                'amount' => $held,
                'reference' => $booking->booking_code,
                'meta' => ['source' => 'backfill'],
            ]);

            if (in_array($status, [
                ExcessLuggageEscrow::STATUS_RELEASED,
                ExcessLuggageEscrow::STATUS_SURPLUS_HELD,
                ExcessLuggageEscrow::STATUS_REFUNDED,
            ], true) && $split['owner'] > 0) {
                app(ExcessLuggageService::class)->creditOwnerShareAtDeposit(
                    $booking->fresh(['bus.campany.balance']),
                    $escrow->fresh(),
                    (float) $split['owner']
                );
            }
        });
    }

    public function down(): void
    {
        ExcessLuggageEscrowTransaction::query()
            ->whereJsonContains('meta->source', 'backfill')
            ->delete();

        ExcessLuggageEscrow::query()->delete();
    }
};
