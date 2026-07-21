<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'actual_length')) {
                $table->decimal('actual_length', 8, 2)->nullable()->after('actual_weight');
            }
            if (!Schema::hasColumn('bookings', 'actual_height')) {
                $table->decimal('actual_height', 8, 2)->nullable()->after('actual_length');
            }
            if (!Schema::hasColumn('bookings', 'actual_width')) {
                $table->decimal('actual_width', 8, 2)->nullable()->after('actual_height');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['actual_length', 'actual_height', 'actual_width']);
        });
    }
};
