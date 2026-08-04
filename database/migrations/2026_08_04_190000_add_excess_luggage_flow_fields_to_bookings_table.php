<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'luggage_status')) {
                $table->string('luggage_status', 32)->nullable()->after('luggage_refund_amount');
            }
            if (!Schema::hasColumn('bookings', 'luggage_payment_ref')) {
                $table->string('luggage_payment_ref', 100)->nullable()->after('luggage_status');
            }
            if (!Schema::hasColumn('bookings', 'luggage_payment_status')) {
                $table->string('luggage_payment_status', 32)->nullable()->after('luggage_payment_ref');
            }
            if (!Schema::hasColumn('bookings', 'luggage_weighed_at')) {
                $table->timestamp('luggage_weighed_at')->nullable()->after('luggage_payment_status');
            }
            if (!Schema::hasColumn('bookings', 'luggage_weighed_by')) {
                $table->unsignedBigInteger('luggage_weighed_by')->nullable()->after('luggage_weighed_at');
            }
            if (!Schema::hasColumn('bookings', 'luggage_assigned_at')) {
                $table->timestamp('luggage_assigned_at')->nullable()->after('luggage_weighed_by');
            }
            if (!Schema::hasColumn('bookings', 'luggage_assigned_by')) {
                $table->unsignedBigInteger('luggage_assigned_by')->nullable()->after('luggage_assigned_at');
            }
            if (!Schema::hasColumn('bookings', 'luggage_retrieved_at')) {
                $table->timestamp('luggage_retrieved_at')->nullable()->after('luggage_assigned_by');
            }
            if (!Schema::hasColumn('bookings', 'luggage_retrieved_by')) {
                $table->unsignedBigInteger('luggage_retrieved_by')->nullable()->after('luggage_retrieved_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $cols = [
                'luggage_status',
                'luggage_payment_ref',
                'luggage_payment_status',
                'luggage_weighed_at',
                'luggage_weighed_by',
                'luggage_assigned_at',
                'luggage_assigned_by',
                'luggage_retrieved_at',
                'luggage_retrieved_by',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('bookings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
