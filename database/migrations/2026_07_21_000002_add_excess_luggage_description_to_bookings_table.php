<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'excess_luggage_description')) {
                $table->string('excess_luggage_description', 500)->nullable()->after('excess_luggage_fee');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'excess_luggage_description')) {
                $table->dropColumn('excess_luggage_description');
            }
        });
    }
};
