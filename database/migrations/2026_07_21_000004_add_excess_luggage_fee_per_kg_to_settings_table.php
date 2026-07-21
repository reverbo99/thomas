<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'excess_luggage_fee_per_kg')) {
                // Informational rate shown on the printed ticket only (estimated
                // weight x this rate = estimated luggage fee). The amount actually
                // charged at checkout stays the flat excess-luggage fee.
                $table->decimal('excess_luggage_fee_per_kg', 10, 2)->default(0)->after('parcel_commission_percentage');
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (Schema::hasColumn('settings', 'excess_luggage_fee_per_kg')) {
                $table->dropColumn('excess_luggage_fee_per_kg');
            }
        });
    }
};
