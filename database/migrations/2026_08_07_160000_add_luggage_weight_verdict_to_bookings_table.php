<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'luggage_weight_verdict')) {
                $table->string('luggage_weight_verdict', 32)->nullable()->after('luggage_refund_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'luggage_weight_verdict')) {
                $table->dropColumn('luggage_weight_verdict');
            }
        });
    }
};
