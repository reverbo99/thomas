<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Points the settings row at the FastHub configuration chosen as default.
 *
 * Kept as a plain nullable column (not a foreign key) so deleting a
 * configuration never blocks or cascades into the settings row; the
 * controller clears the pointer instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('settings', 'sms_gateway_id')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->unsignedBigInteger('sms_gateway_id')->nullable()->after('sms_driver');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('settings', 'sms_gateway_id')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropColumn('sms_gateway_id');
            });
        }
    }
};
