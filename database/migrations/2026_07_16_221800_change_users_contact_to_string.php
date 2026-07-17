<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phone numbers must be stored as strings — INT overflows at 10+ digits
     * (e.g. 123412341234 when creating a coaster/driver).
     */
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'contact')) {
            return;
        }

        DB::statement('ALTER TABLE users MODIFY contact VARCHAR(20) NULL');
    }

    public function down(): void
    {
        if (!Schema::hasColumn('users', 'contact')) {
            return;
        }

        // Truncate values that would not fit in INT before reverting.
        DB::statement('UPDATE users SET contact = NULL WHERE contact IS NOT NULL AND (contact REGEXP \'[^0-9]\' OR CAST(contact AS UNSIGNED) > 2147483647)');
        DB::statement('ALTER TABLE users MODIFY contact INT NULL');
    }
};
