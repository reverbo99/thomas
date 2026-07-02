<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'two_factor_secret')) {
            Schema::table('users', function (Blueprint $table) {
                $table->text('two_factor_secret')
                    ->after('password')
                    ->nullable();
            });
        }

        if (!Schema::hasColumn('users', 'two_factor_recovery_codes')) {
            Schema::table('users', function (Blueprint $table) {
                $table->text('two_factor_recovery_codes')
                    ->after('two_factor_secret')
                    ->nullable();
            });
        }

        if (!Schema::hasColumn('users', 'two_factor_confirmed_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('two_factor_confirmed_at')
                    ->after('two_factor_recovery_codes')
                    ->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columnsToDrop = [];

        if (Schema::hasColumn('users', 'two_factor_secret')) {
            $columnsToDrop[] = 'two_factor_secret';
        }

        if (Schema::hasColumn('users', 'two_factor_recovery_codes')) {
            $columnsToDrop[] = 'two_factor_recovery_codes';
        }

        if (Schema::hasColumn('users', 'two_factor_confirmed_at')) {
            $columnsToDrop[] = 'two_factor_confirmed_at';
        }

        if (!empty($columnsToDrop)) {
            Schema::table('users', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }
    }
};
