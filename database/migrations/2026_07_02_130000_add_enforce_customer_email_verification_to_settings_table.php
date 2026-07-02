<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'enforce_customer_email_verification')) {
                $table->boolean('enforce_customer_email_verification')->default(true)->after('enforce_2fa');
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (Schema::hasColumn('settings', 'enforce_customer_email_verification')) {
                $table->dropColumn('enforce_customer_email_verification');
            }
        });
    }
};
