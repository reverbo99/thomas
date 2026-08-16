<?php

use App\Models\ExcessLuggageEscrow;
use App\Models\ExcessLuggageEscrowTransaction;
use App\Services\ExcessLuggageService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Backfilled escrow rows recorded owner_share but never credited balances.amount.
 * Credit any released owner shares that have no release_owner transaction yet.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('excess_luggage_escrow')) {
            return;
        }

        ExcessLuggageEscrow::query()
            ->whereIn('status', [
                ExcessLuggageEscrow::STATUS_RELEASED,
                ExcessLuggageEscrow::STATUS_SURPLUS_HELD,
                ExcessLuggageEscrow::STATUS_REFUNDED,
            ])
            ->where('owner_share', '>', 0)
            ->whereDoesntHave('transactions', function ($q) {
                $q->where('type', ExcessLuggageEscrowTransaction::TYPE_RELEASE_OWNER);
            })
            ->with(['booking.bus.campany.balance'])
            ->orderBy('id')
            ->chunkById(100, function ($escrows) {
                $service = app(ExcessLuggageService::class);
                foreach ($escrows as $escrow) {
                    if (!$escrow->booking) {
                        continue;
                    }
                    $target = (float) $escrow->owner_share;
                    $escrow->update(['owner_share' => 0]);
                    $service->creditOwnerShareAtDeposit(
                        $escrow->booking,
                        $escrow->fresh(),
                        $target
                    );
                }
            });
    }

    public function down(): void
    {
        // Irreversible wallet credits — no automatic rollback.
    }
};
