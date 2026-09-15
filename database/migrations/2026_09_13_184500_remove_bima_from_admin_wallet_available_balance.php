<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Paid insurance (bima) was historically credited into admin_wallet.balance, so
 * Available Balance incorrectly included insurer funds. Mark which bima rows were
 * credited, strip that total from the wallet once, and stop double-counting.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bima') || !Schema::hasTable('admin_wallet')) {
            return;
        }

        if (!Schema::hasColumn('bima', 'credited_to_admin_wallet')) {
            Schema::table('bima', function (Blueprint $table) {
                $table->boolean('credited_to_admin_wallet')->default(true)->after('bima_vat');
            });
        }

        $creditedTotal = (float) DB::table('bima')
            ->where('credited_to_admin_wallet', 1)
            ->sum('amount');

        if ($creditedTotal > 0) {
            $wallet = DB::table('admin_wallet')->orderBy('id')->first();
            if ($wallet) {
                $corrected = max(0, (float) ($wallet->balance ?? 0) - $creditedTotal);
                DB::table('admin_wallet')
                    ->where('id', $wallet->id)
                    ->update([
                        'balance' => $corrected,
                        'updated_at' => now(),
                    ]);
            }

            DB::table('bima')
                ->where('credited_to_admin_wallet', 1)
                ->update(['credited_to_admin_wallet' => 0]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('bima')) {
            return;
        }

        // Cannot safely re-credit wallet without knowing prior amounts; only drop the flag.
        if (Schema::hasColumn('bima', 'credited_to_admin_wallet')) {
            Schema::table('bima', function (Blueprint $table) {
                $table->dropColumn('credited_to_admin_wallet');
            });
        }
    }
};
