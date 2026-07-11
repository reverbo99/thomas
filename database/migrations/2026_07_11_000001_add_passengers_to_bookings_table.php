<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('bookings', 'passengers')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->json('passengers')->nullable()->after('customer_name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bookings', 'passengers')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropColumn('passengers');
            });
        }
    }
};
