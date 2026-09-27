<?php

use App\Services\ParcelFlowService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parcels', function (Blueprint $table) {
            if (!Schema::hasColumn('parcels', 'admin_share')) {
                $table->decimal('admin_share', 12, 2)->nullable()->after('amount_paid');
            }
            if (!Schema::hasColumn('parcels', 'vendor_share')) {
                $table->decimal('vendor_share', 12, 2)->nullable()->after('admin_share');
            }
            if (!Schema::hasColumn('parcels', 'government_levy')) {
                $table->decimal('government_levy', 12, 2)->nullable()->after('vendor_share');
            }
            if (!Schema::hasColumn('parcels', 'owner_share')) {
                $table->decimal('owner_share', 12, 2)->nullable()->after('government_levy');
            }
        });

        // Parcels settled before the government levy was introduced: record the split
        // that was actually credited (commission pool only, no levy) so reports keep
        // matching the bus-owner / vendor / admin balances.
        $commission = ParcelFlowService::commissionPercent();
        DB::table('parcels')
            ->where('payment_status', ParcelFlowService::PAY_PAID)
            ->whereNull('owner_share')
            ->orderBy('id')
            ->get(['id', 'amount_paid', 'vender_id'])
            ->each(function ($parcel) use ($commission) {
                $hasVendor = !empty($parcel->vender_id);
                $split = ParcelFlowService::splitAmounts(
                    (float) $parcel->amount_paid,
                    $hasVendor,
                    $commission,
                    $hasVendor ? ParcelFlowService::vendorPoolPercent($parcel->vender_id) : 0.0,
                    0.0
                );

                DB::table('parcels')->where('id', $parcel->id)->update([
                    'admin_share' => $split['admin'],
                    'vendor_share' => $split['vendor'],
                    'government_levy' => 0,
                    'owner_share' => $split['owner'],
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('parcels', function (Blueprint $table) {
            foreach (['admin_share', 'vendor_share', 'government_levy', 'owner_share'] as $column) {
                if (Schema::hasColumn('parcels', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
