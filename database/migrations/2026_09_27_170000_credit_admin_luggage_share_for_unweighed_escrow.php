<?php

use App\Models\Booking;
use App\Models\ExcessLuggageEscrow;
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

        $service = app(ExcessLuggageService::class);

        ExcessLuggageEscrow::query()
            ->whereIn('status', [
                ExcessLuggageEscrow::STATUS_HELD,
                ExcessLuggageEscrow::STATUS_AWAITING_TOPUP,
            ])
            ->where(function ($q) {
                $q->whereNull('admin_share')->orWhere('admin_share', '<=', 0);
            })
            ->orderBy('id')
            ->chunkById(200, function ($escrows) use ($service) {
                foreach ($escrows as $escrow) {
                    $booking = Booking::find($escrow->booking_id);
                    if (!$booking || ($booking->payment_status ?? '') !== 'Paid') {
                        continue;
                    }

                    $gross = (float) ($escrow->estimated_fee ?: $escrow->held_amount);
                    $adminShare = split_luggage_fee_amount($gross)['system'];

                    DB::transaction(fn () => $service->creditAdminShare($booking, $escrow, $adminShare));
                }
            });
    }

    public function down(): void
    {
    }
};
