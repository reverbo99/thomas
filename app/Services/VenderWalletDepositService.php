<?php

namespace App\Services;

use App\Models\VenderBalance;
use App\Models\VenderWalletDeposit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VenderWalletDepositService
{
    /**
     * Credit a vendor cash wallet exactly once for a unique deposit reference.
     */
    public function settleTestDeposit(int $userId, float $amount, string $reference): VenderWalletDeposit
    {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Deposit amount must be greater than zero.');
        }
        if (!Schema::hasColumn('vender_balances', 'sell_cash_amount')) {
            throw new \RuntimeException('Vendor cash wallet is not available.');
        }

        return DB::transaction(function () use ($userId, $amount, $reference) {
            VenderWalletDeposit::query()->insertOrIgnore([
                'user_id' => $userId,
                'amount' => $amount,
                'payment_method' => 'test_mode',
                'reference' => $reference,
                'status' => VenderWalletDeposit::STATUS_PENDING,
                'metadata' => json_encode(['source' => 'vendor_wallet_deposit_form']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $deposit = VenderWalletDeposit::query()
                ->where('reference', $reference)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $deposit->user_id !== $userId
                || abs((float) $deposit->amount - $amount) > 0.001
                || $deposit->payment_method !== 'test_mode') {
                throw new \RuntimeException('Deposit reference does not match this request.');
            }

            if ($deposit->status === VenderWalletDeposit::STATUS_COMPLETED) {
                return $deposit;
            }

            $balance = VenderBalance::query()
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if (!$balance) {
                throw new \RuntimeException('Vendor wallet does not exist.');
            }

            $balance->increment('sell_cash_amount', $amount);
            $deposit->update([
                'status' => VenderWalletDeposit::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);

            return $deposit->fresh();
        }, 3);
    }
}
