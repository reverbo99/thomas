<?php

namespace Tests\Unit;

use App\Models\VenderBalance;
use App\Models\VenderWalletDeposit;
use App\Services\VenderWalletDepositService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VenderWalletDepositServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => false,
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        DB::reconnect('sqlite');

        Schema::create('vender_balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->decimal('amount', 14, 2)->default(0);
            $table->decimal('sell_cash_amount', 14, 2)->default(0);
            $table->decimal('fees', 14, 2)->default(0);
            $table->string('payment_number')->nullable();
            $table->timestamps();
        });

        Schema::create('vender_wallet_deposits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->decimal('amount', 14, 2);
            $table->string('payment_method', 32);
            $table->string('reference', 64)->unique();
            $table->string('status', 24)->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function test_same_reference_credits_cash_wallet_only_once(): void
    {
        VenderBalance::create([
            'user_id' => 42,
            'amount' => 10,
            'sell_cash_amount' => 100,
            'fees' => 0,
        ]);

        $service = app(VenderWalletDepositService::class);
        $service->settleTestDeposit(42, 25, 'TESTVWDEP42T202608150001ABC123');
        $service->settleTestDeposit(42, 25, 'TESTVWDEP42T202608150001ABC123');

        $this->assertSame('125.00', VenderBalance::where('user_id', 42)->value('sell_cash_amount'));
        $this->assertSame(1, VenderWalletDeposit::count());
        $this->assertSame(
            VenderWalletDeposit::STATUS_COMPLETED,
            VenderWalletDeposit::first()->status
        );
    }

    public function test_reference_cannot_be_reused_for_a_different_amount(): void
    {
        VenderBalance::create([
            'user_id' => 42,
            'amount' => 0,
            'sell_cash_amount' => 100,
            'fees' => 0,
        ]);

        $service = app(VenderWalletDepositService::class);
        $service->settleTestDeposit(42, 25, 'TESTVWDEP42T202608150002ABC123');

        try {
            $service->settleTestDeposit(42, 50, 'TESTVWDEP42T202608150002ABC123');
            $this->fail('Expected a mismatched deposit reference to be rejected.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Deposit reference does not match this request.', $e->getMessage());
        }

        $this->assertSame('125.00', VenderBalance::where('user_id', 42)->value('sell_cash_amount'));
        $this->assertSame(1, VenderWalletDeposit::count());
    }
}
