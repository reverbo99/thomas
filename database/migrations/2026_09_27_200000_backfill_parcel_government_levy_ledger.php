<?php

use App\Models\GovernmentLevy;
use App\Models\Parcel;
use App\Services\ParcelFlowService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Record statutory parcel levy in government_levies for paid parcels that
     * settled before levy was posted. Does not adjust bus-owner balances.
     */
    public function up(): void
    {
        if (!Schema::hasTable('parcels') || !Schema::hasColumn('parcels', 'government_levy')) {
            return;
        }

        Parcel::query()
            ->with('bus.campany')
            ->where('payment_status', ParcelFlowService::PAY_PAID)
            ->where('status', '!=', ParcelFlowService::STATUS_CANCELLED)
            ->where(function ($q) {
                $q->whereNull('government_levy')->orWhere('government_levy', '<=', 0);
            })
            ->orderBy('id')
            ->chunkById(100, function ($parcels) {
                foreach ($parcels as $parcel) {
                    $amount = max(0.0, round((float) $parcel->amount_paid, 2));
                    if ($amount <= 0) {
                        continue;
                    }

                    $levy = government_levy_on_amount($amount);
                    if ($levy <= 0) {
                        continue;
                    }

                    $campanyId = $parcel->bus?->campany?->id;
                    if (!$campanyId) {
                        continue;
                    }

                    $ref = (string) $parcel->parcel_number;
                    $exists = GovernmentLevy::query()
                        ->where('booking_id', $ref)
                        ->where('amount', $levy)
                        ->exists();

                    if (!$exists) {
                        GovernmentLevy::create([
                            'campany_id' => $campanyId,
                            'booking_id' => $ref,
                            'amount' => $levy,
                        ]);
                    }

                    $parcel->update(['government_levy' => $levy]);
                }
            });
    }

    public function down(): void
    {
        // Ledger backfill only.
    }
};
