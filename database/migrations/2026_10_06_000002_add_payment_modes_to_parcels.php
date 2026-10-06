<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Parcel payment modes: cash (paid in full at registration), instalment
 * (a deposit now, balance collected in cash at the destination) and COD
 * (nothing at registration, the full fee collected in cash at the destination).
 *
 * `amount_paid` keeps its historical meaning: the TOTAL parcel fee.
 * `paid_amount` is what has actually been collected so far and `balance_due`
 * is what is still owed before the wallets are credited.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parcels', function (Blueprint $table) {
            if (!Schema::hasColumn('parcels', 'payment_mode')) {
                $table->string('payment_mode', 20)->default('cash')->after('payment_ref');
            }
            if (!Schema::hasColumn('parcels', 'paid_amount')) {
                $table->decimal('paid_amount', 12, 2)->default(0)->after('payment_mode');
            }
            if (!Schema::hasColumn('parcels', 'balance_due')) {
                $table->decimal('balance_due', 12, 2)->default(0)->after('paid_amount');
            }
            if (!Schema::hasColumn('parcels', 'deposit_paid_at')) {
                $table->timestamp('deposit_paid_at')->nullable()->after('balance_due');
            }
            if (!Schema::hasColumn('parcels', 'balance_paid_at')) {
                $table->timestamp('balance_paid_at')->nullable()->after('deposit_paid_at');
            }
        });

        // Backfill existing rows: a settled parcel was paid in full in cash;
        // every other parcel is treated as still owing its whole fee.
        DB::table('parcels')->whereNull('payment_mode')->update(['payment_mode' => 'cash']);

        DB::table('parcels')
            ->where('payment_status', 'paid')
            ->update([
                'paid_amount' => DB::raw('COALESCE(amount_paid, 0)'),
                'balance_due' => 0,
            ]);

        DB::table('parcels')
            ->where(function ($query) {
                $query->where('payment_status', '!=', 'paid')
                    ->orWhereNull('payment_status');
            })
            ->update([
                'paid_amount' => 0,
                'balance_due' => DB::raw('COALESCE(amount_paid, 0)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('parcels', function (Blueprint $table) {
            foreach (['payment_mode', 'paid_amount', 'balance_due', 'deposit_paid_at', 'balance_paid_at'] as $column) {
                if (Schema::hasColumn('parcels', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
