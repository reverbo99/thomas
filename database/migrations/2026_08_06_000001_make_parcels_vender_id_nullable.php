<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bus owners register parcels with vender_id = null (created_by holds the actor).
     * Original parcels.vender_id was NOT NULL + FK, which breaks ClickPesa register.
     */
    public function up(): void
    {
        if (!Schema::hasTable('parcels') || !Schema::hasColumn('parcels', 'vender_id')) {
            return;
        }

        $fk = collect(DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL',
            ['parcels', 'vender_id']
        ))->pluck('CONSTRAINT_NAME')->first();

        if ($fk) {
            Schema::table('parcels', function (Blueprint $table) use ($fk) {
                $table->dropForeign($fk);
            });
        }

        DB::statement('ALTER TABLE parcels MODIFY vender_id BIGINT UNSIGNED NULL');

        Schema::table('parcels', function (Blueprint $table) {
            $table->foreign('vender_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Leave nullable — restoring NOT NULL would fail if owner-created rows exist.
    }
};
