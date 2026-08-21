<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Allow customers to request a refund without immediately marking payment refunded.
     * payment_status was ENUM('pending','paid','refunded') — add refund_pending.
     */
    public function up(): void
    {
        if (! Schema::hasTable('special_hire_orders')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE special_hire_orders MODIFY payment_status ENUM('pending','paid','refunded','refund_pending') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('special_hire_orders')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            // Revert pending requests to paid so the narrower enum can be restored.
            DB::table('special_hire_orders')
                ->where('payment_status', 'refund_pending')
                ->update(['payment_status' => 'paid']);

            DB::statement("ALTER TABLE special_hire_orders MODIFY payment_status ENUM('pending','paid','refunded') NOT NULL DEFAULT 'pending'");
        }
    }
};
