<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'parcel_fee_per_kg')) {
                $table->decimal('parcel_fee_per_kg', 10, 2)->default(0)->after('excess_luggage_fee_per_kg');
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (Schema::hasColumn('settings', 'parcel_fee_per_kg')) {
                $table->dropColumn('parcel_fee_per_kg');
            }
        });
    }
};
