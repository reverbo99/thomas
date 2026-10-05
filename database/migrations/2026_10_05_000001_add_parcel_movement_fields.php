<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Parcel movement redesign: explicit store -> load -> destination-receive
     * stages, conductor details at load, and collector identity + signature
     * at handover.
     */
    public function up(): void
    {
        Schema::table('parcels', function (Blueprint $table) {
            if (!Schema::hasColumn('parcels', 'conductor_name')) {
                $table->string('conductor_name', 150)->nullable()->after('delivery_rider_phone');
            }
            if (!Schema::hasColumn('parcels', 'conductor_phone')) {
                $table->string('conductor_phone', 40)->nullable()->after('conductor_name');
            }
            if (!Schema::hasColumn('parcels', 'loaded_at')) {
                $table->timestamp('loaded_at')->nullable()->after('departed_at');
            }
            if (!Schema::hasColumn('parcels', 'loaded_by_user_id')) {
                $table->unsignedBigInteger('loaded_by_user_id')->nullable()->after('loaded_at');
            }
            if (!Schema::hasColumn('parcels', 'collector_name')) {
                $table->string('collector_name', 150)->nullable()->after('conductor_phone');
            }
            if (!Schema::hasColumn('parcels', 'collector_phone')) {
                $table->string('collector_phone', 40)->nullable()->after('collector_name');
            }
            if (!Schema::hasColumn('parcels', 'collector_signature')) {
                $table->longText('collector_signature')->nullable()->after('collector_phone');
            }
            if (!Schema::hasColumn('parcels', 'collected_by_user_id')) {
                $table->unsignedBigInteger('collected_by_user_id')->nullable()->after('collector_signature');
            }
        });

        // Repoint the legacy lifecycle onto the new movement stages:
        //   received  (office intake)  -> in_store
        //   in_transit (dispatched)    -> loaded
        //   arrived   (destination)    -> received
        DB::table('parcels')->where('status', 'received')->update(['status' => 'in_store']);
        DB::table('parcels')->where('status', 'in_transit')->update(['status' => 'loaded']);
        DB::table('parcels')->where('status', 'arrived')->update(['status' => 'received']);
    }

    public function down(): void
    {
        // Reverse in the order that keeps the restored legacy statuses distinct.
        DB::table('parcels')->where('status', 'received')->update(['status' => 'arrived']);
        DB::table('parcels')->where('status', 'loaded')->update(['status' => 'in_transit']);
        DB::table('parcels')->where('status', 'in_store')->update(['status' => 'received']);

        Schema::table('parcels', function (Blueprint $table) {
            foreach ([
                'conductor_name', 'conductor_phone', 'loaded_at', 'loaded_by_user_id',
                'collector_name', 'collector_phone', 'collector_signature', 'collected_by_user_id',
            ] as $col) {
                if (Schema::hasColumn('parcels', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
