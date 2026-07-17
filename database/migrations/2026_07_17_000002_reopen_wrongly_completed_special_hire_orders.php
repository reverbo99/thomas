<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Reopen paid hires that were wrongly marked completed by customer paperwork
 * (payment / passenger save). Trip completion is driver-only going forward.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::update("
            UPDATE special_hire_orders
            SET order_status = 'confirmed',
                owner_accepted_at = COALESCE(owner_accepted_at, NOW()),
                updated_at = NOW()
            WHERE payment_status = 'paid'
              AND order_status = 'completed'
              AND hire_date >= CURDATE()
        ");
    }

    public function down(): void
    {
        // Irreversible data repair — cannot restore which rows were wrongly completed.
    }
};
