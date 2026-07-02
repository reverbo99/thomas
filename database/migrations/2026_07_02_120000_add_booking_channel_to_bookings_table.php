<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('bookings', 'booking_channel')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->string('booking_channel', 20)->nullable()->after('payment_method');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bookings', 'booking_channel')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropColumn('booking_channel');
            });
        }
    }
};
