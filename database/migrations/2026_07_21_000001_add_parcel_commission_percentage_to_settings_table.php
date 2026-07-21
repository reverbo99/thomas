<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'parcel_commission_percentage')) {
                $table->decimal('parcel_commission_percentage', 5, 2)->default(0)->after('service_percentage');
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (Schema::hasColumn('settings', 'parcel_commission_percentage')) {
                $table->dropColumn('parcel_commission_percentage');
            }
        });
    }
};
