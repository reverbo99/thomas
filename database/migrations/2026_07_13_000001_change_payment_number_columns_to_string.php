<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Payout details may be a bank string ("Bank name — Account") or a mobile
        // number with a leading zero, neither of which fits in an INT column.
        if (Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'payment_number')) {
            DB::statement("ALTER TABLE `transactions` MODIFY `payment_number` VARCHAR(255) NULL");
        }

        if (Schema::hasTable('vender_balances') && Schema::hasColumn('vender_balances', 'payment_number')) {
            DB::statement("ALTER TABLE `vender_balances` MODIFY `payment_number` VARCHAR(255) NULL");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'payment_number')) {
            DB::statement("ALTER TABLE `transactions` MODIFY `payment_number` INT(11) NULL");
        }

        if (Schema::hasTable('vender_balances') && Schema::hasColumn('vender_balances', 'payment_number')) {
            DB::statement("ALTER TABLE `vender_balances` MODIFY `payment_number` INT(11) NULL");
        }
    }
};
