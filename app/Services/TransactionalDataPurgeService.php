<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TransactionalDataPurgeService
{
    /**
     * Tables to delete in dependency-safe order (children before parents).
     *
     * @var list<string>
     */
    private const DELETE_TABLES = [
        'excess_luggage_escrow_transactions',
        'excess_luggage_escrow',
        'bima',
        'payment_fees',
        'system_balance',
        'government_levies',
        'refund_percentages',
        'refund',
        'cancelled_bookings',
        'roundtrip',
        'temp_wallets',
        'vender_transactions',
        'transactions',
        'admin_transactions',
        'vender_wallet_deposits',
        'parcels',
        'special_hire_payment_intents',
        'special_hire_orders',
        'special_hire_withdrawal_requests',
        'bookings',
    ];

    /**
     * Purge transactional data and reset wallet balances.
     *
     * @return array{deleted: array<string, int>, reset: array<string, int>}
     */
    public function purge(): array
    {
        return DB::transaction(function () {
            $deleted = [];

            foreach (self::DELETE_TABLES as $table) {
                $deleted[$table] = $this->deleteAllFromTable($table);
            }

            $reset = $this->resetWalletBalances();

            return [
                'deleted' => $deleted,
                'reset' => $reset,
            ];
        });
    }

    private function deleteAllFromTable(string $table): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        $count = (int) DB::table($table)->count();

        if ($count === 0) {
            return 0;
        }

        DB::table($table)->delete();

        return $count;
    }

    /**
     * @return array<string, int>
     */
    private function resetWalletBalances(): array
    {
        $reset = [];

        if (Schema::hasTable('balances')) {
            $update = [
                'amount' => 0,
                'fees' => 0,
            ];
            if (Schema::hasColumn('balances', 'updated_at')) {
                $update['updated_at'] = now();
            }
            $reset['balances'] = DB::table('balances')->update($update);
        }

        if (Schema::hasTable('vender_balances')) {
            $update = [
                'amount' => 0,
                'fees' => 0,
            ];

            if (Schema::hasColumn('vender_balances', 'sell_cash_amount')) {
                $update['sell_cash_amount'] = 0;
            }
            if (Schema::hasColumn('vender_balances', 'updated_at')) {
                $update['updated_at'] = now();
            }

            $reset['vender_balances'] = DB::table('vender_balances')->update($update);
        }

        if (Schema::hasTable('admin_wallet')) {
            $update = [
                'service_balance' => 0,
                'commision_balance' => 0,
                'balance' => 0,
                'vat' => 0,
            ];
            if (Schema::hasColumn('admin_wallet', 'updated_at')) {
                $update['updated_at'] = now();
            }
            $reset['admin_wallet'] = DB::table('admin_wallet')->update($update);
        }

        if (Schema::hasTable('discount') && Schema::hasColumn('discount', 'used')) {
            $update = ['used' => 0];
            if (Schema::hasColumn('discount', 'updated_at')) {
                $update['updated_at'] = now();
            }
            $reset['discount.used'] = DB::table('discount')->update($update);
        }

        return $reset;
    }
}
