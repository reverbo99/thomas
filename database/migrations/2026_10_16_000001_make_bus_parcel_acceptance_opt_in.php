<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Parcel transport on a bus is opt-in.
 *
 * `accept_parcels` was created with `default(true)`, so every registered bus
 * was treated as parcel-capable even when its owner had never enabled parcels.
 * Vendors therefore saw "all buses" in the parcel bus picker.
 *
 * This migration:
 *  1. flips the column default to false so newly created buses start disabled, and
 *  2. disables buses that were only "accepting" because of the old default
 *     (no parcels ever booked on them and no parcel capacity configured).
 *
 * Buses that already carry parcels or have a configured capacity keep their
 * current setting, and a bus owner can enable/disable any bus from
 * "Usimamizi wa Mizigo" at any time.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->setColumnDefault(false);

        DB::table('buses')
            ->where('accept_parcels', true)
            ->whereNull('max_parcel_weight_kg')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('parcels')
                    ->whereColumn('parcels.bus_id', 'buses.id');
            })
            ->update(['accept_parcels' => false]);
    }

    public function down(): void
    {
        $this->setColumnDefault(true);
    }

    /**
     * `->change()` needs doctrine/dbal (not installed on Laravel 10), so the
     * default is swapped with a driver-specific statement.
     */
    private function setColumnDefault(bool $accepting): void
    {
        if (!Schema::hasTable('buses') || !Schema::hasColumn('buses', 'accept_parcels')) {
            return;
        }

        $default = $accepting ? '1' : '0';

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `buses` MODIFY `accept_parcels` TINYINT(1) NOT NULL DEFAULT {$default}");

            return;
        }

        // sqlite (test databases) rebuilds the table on each migration run, so
        // there is no persistent default to change there.
    }
};
