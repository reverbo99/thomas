<?php

use App\Models\Parcel;
use App\Services\ParcelFlowService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Parcels settled before government levy deducted 5% from the bus-owner credit.
     * Claw back the over-credit and sync parcels.owner_share to fee − admin − vendor − levy.
     */
    public function up(): void
    {
        if (!Schema::hasTable('parcels') || !Schema::hasColumn('parcels', 'owner_share')) {
            return;
        }

        Parcel::query()
            ->with('bus.campany.balance')
            ->where('payment_status', ParcelFlowService::PAY_PAID)
            ->where('status', '!=', ParcelFlowService::STATUS_CANCELLED)
            ->whereNotNull('owner_share')
            ->orderBy('id')
            ->chunkById(100, function ($parcels) {
                foreach ($parcels as $parcel) {
                    $split = ParcelFlowService::splitForParcel($parcel);
                    $correctOwner = (float) $split['owner'];
                    $storedOwner = round((float) $parcel->owner_share, 2);
                    $delta = round($storedOwner - $correctOwner, 2);

                    if ($delta < 0.005) {
                        if (abs($storedOwner - $correctOwner) >= 0.005) {
                            $parcel->update(['owner_share' => $correctOwner]);
                        }

                        continue;
                    }

                    DB::transaction(function () use ($parcel, $correctOwner, $delta, $storedOwner) {
                        $campany = $parcel->bus?->campany;
                        if ($campany?->balance) {
                            $campany->balance->decrement('amount', $delta);
                        }

                        $parcel->update(['owner_share' => $correctOwner]);

                        Log::info('Parcel owner balance reconciled for government levy', [
                            'parcel_id' => $parcel->id,
                            'parcel_number' => $parcel->parcel_number,
                            'campany_id' => $campany?->id,
                            'from_owner_share' => $storedOwner,
                            'to_owner_share' => $correctOwner,
                            'balance_delta' => -$delta,
                        ]);
                    });
                }
            });
    }

    public function down(): void
    {
        // Data correction only.
    }
};
