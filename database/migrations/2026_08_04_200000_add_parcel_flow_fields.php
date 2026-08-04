<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parcels', function (Blueprint $table) {
            if (!Schema::hasColumn('parcels', 'payment_status')) {
                $table->string('payment_status', 32)->default('unpaid')->after('amount_paid');
            }
            if (!Schema::hasColumn('parcels', 'payment_method')) {
                $table->string('payment_method', 40)->nullable()->after('payment_status');
            }
            if (!Schema::hasColumn('parcels', 'payment_ref')) {
                $table->string('payment_ref', 100)->nullable()->after('payment_method');
            }
            if (!Schema::hasColumn('parcels', 'receiving_user_id')) {
                $table->unsignedBigInteger('receiving_user_id')->nullable()->after('vender_id');
            }
            if (!Schema::hasColumn('parcels', 'receiving_agent_name')) {
                $table->string('receiving_agent_name', 150)->nullable()->after('receiving_user_id');
            }
            if (!Schema::hasColumn('parcels', 'receiving_agent_phone')) {
                $table->string('receiving_agent_phone', 40)->nullable()->after('receiving_agent_name');
            }
            if (!Schema::hasColumn('parcels', 'delivery_rider_name')) {
                $table->string('delivery_rider_name', 150)->nullable()->after('receiving_agent_phone');
            }
            if (!Schema::hasColumn('parcels', 'delivery_rider_phone')) {
                $table->string('delivery_rider_phone', 40)->nullable()->after('delivery_rider_name');
            }
            if (!Schema::hasColumn('parcels', 'settled_at')) {
                $table->timestamp('settled_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('parcels', 'departed_at')) {
                $table->timestamp('departed_at')->nullable()->after('settled_at');
            }
            if (!Schema::hasColumn('parcels', 'arrived_at')) {
                $table->timestamp('arrived_at')->nullable()->after('departed_at');
            }
            if (!Schema::hasColumn('parcels', 'collected_at')) {
                $table->timestamp('collected_at')->nullable()->after('arrived_at');
            }
            if (!Schema::hasColumn('parcels', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('vender_id');
            }
        });

        Schema::table('buses', function (Blueprint $table) {
            if (!Schema::hasColumn('buses', 'max_parcel_weight_kg')) {
                $table->decimal('max_parcel_weight_kg', 10, 2)->nullable()->after('accept_parcels');
            }
        });
    }

    public function down(): void
    {
        Schema::table('parcels', function (Blueprint $table) {
            foreach ([
                'payment_status', 'payment_method', 'payment_ref',
                'receiving_user_id', 'receiving_agent_name', 'receiving_agent_phone',
                'delivery_rider_name', 'delivery_rider_phone',
                'settled_at', 'departed_at', 'arrived_at', 'collected_at', 'created_by',
            ] as $col) {
                if (Schema::hasColumn('parcels', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('buses', function (Blueprint $table) {
            if (Schema::hasColumn('buses', 'max_parcel_weight_kg')) {
                $table->dropColumn('max_parcel_weight_kg');
            }
        });
    }
};
