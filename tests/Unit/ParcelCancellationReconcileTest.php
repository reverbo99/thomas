<?php

namespace Tests\Unit;

use App\Models\AdminWallet;
use App\Models\balance;
use App\Models\bus;
use App\Models\Campany;
use App\Models\GovernmentLevy;
use App\Models\Parcel;
use App\Models\Setting;
use App\Models\VenderAccount;
use App\Models\VenderBalance;
use App\Services\ParcelFlowService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ParcelCancellationReconcileTest extends TestCase
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

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('parcel_commission_percentage', 8, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('vender_account', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->decimal('percentage', 8, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('campanies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('buses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campany_id')->nullable();
            $table->string('bus_number')->nullable();
            $table->timestamps();
        });

        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bus_id')->nullable();
            $table->string('from')->nullable();
            $table->string('to')->nullable();
            $table->timestamps();
        });

        Schema::create('balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campany_id')->nullable();
            $table->decimal('amount', 14, 2)->default(0);
            $table->decimal('fees', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('admin_wallet', function (Blueprint $table) {
            $table->id();
            $table->decimal('service_balance', 14, 2)->default(0);
            $table->decimal('commision_balance', 14, 2)->default(0);
            $table->decimal('balance', 14, 2)->default(0);
            $table->decimal('vat', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('government_levies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campany_id')->nullable();
            $table->string('booking_id')->nullable();
            $table->decimal('amount', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('vender_balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->decimal('amount', 14, 2)->default(0);
            $table->decimal('sell_cash_amount', 14, 2)->default(0);
            $table->decimal('fees', 14, 2)->default(0);
            $table->string('payment_number')->nullable();
            $table->timestamps();
        });

        Schema::create('parcels', function (Blueprint $table) {
            $table->id();
            $table->string('parcel_number')->unique();
            $table->string('parcel_type')->nullable();
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('admin_share', 12, 2)->nullable();
            $table->decimal('vendor_share', 12, 2)->nullable();
            $table->decimal('government_levy', 12, 2)->nullable();
            $table->decimal('owner_share', 12, 2)->nullable();
            $table->string('payment_status')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_ref')->nullable();
            $table->string('payment_mode')->default('cash');
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('balance_due', 12, 2)->default(0);
            $table->timestamp('deposit_paid_at')->nullable();
            $table->timestamp('balance_paid_at')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->timestamp('commission_reversed_at')->nullable();
            $table->string('status')->default('registered');
            $table->unsignedBigInteger('bus_id')->nullable();
            $table->unsignedBigInteger('vender_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Seed a company, bus, balances wallet, admin wallet, vendor account and vendor wallet.
     *
     * @return array{0: Campany, 1: bus}
     */
    private function seedWorld(): array
    {
        Setting::create(['parcel_commission_percentage' => 5]);
        VenderAccount::create(['user_id' => 7, 'percentage' => 10]);

        $campany = Campany::create(['name' => 'Test Co']);
        $bus = bus::create(['campany_id' => $campany->id, 'bus_number' => 'T-100']);

        balance::create(['campany_id' => $campany->id, 'amount' => 0, 'fees' => 0]);
        AdminWallet::create([
            'service_balance' => 0,
            'commision_balance' => 0,
            'balance' => 0,
            'vat' => 0,
        ]);
        VenderBalance::create([
            'user_id' => 7,
            'amount' => 0,
            'sell_cash_amount' => 0,
            'fees' => 0,
        ]);

        return [$campany, $bus];
    }

    private function makeUnpaidParcel(bus $bus, string $number): Parcel
    {
        return Parcel::create([
            'parcel_number' => $number,
            'parcel_type' => 'Box',
            'bus_id' => $bus->id,
            'vender_id' => 7,
            'amount_paid' => 10000,
            'payment_status' => ParcelFlowService::PAY_UNPAID,
            'payment_mode' => ParcelFlowService::MODE_CASH,
            'paid_amount' => 0,
            'balance_due' => 10000,
            'status' => ParcelFlowService::STATUS_REGISTERED,
        ]);
    }

    public function test_pre_fix_cancelled_settled_parcel_is_repaired_exactly_once(): void
    {
        [$campany, $bus] = $this->seedWorld();
        $parcel = $this->makeUnpaidParcel($bus, 'PCL-3001');
        $flow = app(ParcelFlowService::class);

        $flow->settleFully($parcel, 'TESTREF3001', 'test');

        // Settlement credited every wallet: admin 450, owner 9000, vendor 50, levy 500.
        $parcel->refresh();
        $this->assertEqualsWithDelta(450.0, (float) AdminWallet::query()->first()->balance, 0.001);
        $this->assertEqualsWithDelta(9000.0, (float) $campany->balance()->first()->amount, 0.001);
        $this->assertEqualsWithDelta(50.0, (float) VenderBalance::where('user_id', 7)->value('amount'), 0.001);
        $this->assertSame(1, GovernmentLevy::where('booking_id', 'PCL-3001')->count());

        // Simulate a PRE-FIX cancellation: status flipped without any reversal.
        Parcel::whereKey($parcel->id)->update([
            'status' => ParcelFlowService::STATUS_CANCELLED,
            'commission_reversed_at' => null,
        ]);

        // The wallets are still overstated at this point.
        $this->assertEqualsWithDelta(450.0, (float) AdminWallet::query()->first()->balance, 0.001);

        $result = $flow->reverseSettledParcelIfNeeded($parcel->fresh());

        $this->assertTrue($result['reversed']);
        $this->assertEqualsWithDelta(450.0, $result['admin'], 0.001);
        $this->assertEqualsWithDelta(9000.0, $result['owner'], 0.001);
        $this->assertEqualsWithDelta(50.0, $result['vendor'], 0.001);
        $this->assertEqualsWithDelta(500.0, $result['government'], 0.001);

        $parcel->refresh();
        $this->assertNotNull($parcel->commission_reversed_at);
        // Cancellation must never change the payment status.
        $this->assertSame(ParcelFlowService::PAY_PAID, $parcel->payment_status);
        $this->assertSame(ParcelFlowService::STATUS_CANCELLED, $parcel->status);

        $this->assertEqualsWithDelta(0.0, (float) AdminWallet::query()->first()->balance, 0.001);
        $this->assertEqualsWithDelta(0.0, (float) $campany->balance()->first()->amount, 0.001);
        $this->assertEqualsWithDelta(0.0, (float) VenderBalance::where('user_id', 7)->value('amount'), 0.001);
        $this->assertSame(0, GovernmentLevy::where('booking_id', 'PCL-3001')->count());

        // A second repair call is a no-op.
        $second = $flow->reverseSettledParcelIfNeeded($parcel->fresh());
        $this->assertFalse($second['reversed']);
        $this->assertEqualsWithDelta(0.0, $second['owner'], 0.001);
        $this->assertEqualsWithDelta(0.0, $second['admin'], 0.001);
        $this->assertEqualsWithDelta(0.0, $second['vendor'], 0.001);
        $this->assertEqualsWithDelta(0.0, $second['government'], 0.001);

        // Nothing moved again.
        $this->assertEqualsWithDelta(0.0, (float) AdminWallet::query()->first()->balance, 0.001);
        $this->assertEqualsWithDelta(0.0, (float) $campany->balance()->first()->amount, 0.001);
        $this->assertEqualsWithDelta(0.0, (float) VenderBalance::where('user_id', 7)->value('amount'), 0.001);
    }

    public function test_cancel_with_reversal_sets_marker_and_reverses_once(): void
    {
        [$campany, $bus] = $this->seedWorld();
        $parcel = $this->makeUnpaidParcel($bus, 'PCL-3002');
        $flow = app(ParcelFlowService::class);

        $flow->settleFully($parcel, 'TESTREF3002', 'test');
        $parcel->refresh();
        $this->assertNull($parcel->commission_reversed_at);

        $flow->cancelWithReversal($parcel);

        $parcel->refresh();
        $this->assertSame(ParcelFlowService::STATUS_CANCELLED, $parcel->status);
        $this->assertSame(ParcelFlowService::PAY_PAID, $parcel->payment_status);
        $this->assertNotNull($parcel->commission_reversed_at);
        $this->assertEqualsWithDelta(0.0, (float) AdminWallet::query()->first()->balance, 0.001);
        $this->assertEqualsWithDelta(0.0, (float) $campany->balance()->first()->amount, 0.001);
        $this->assertEqualsWithDelta(0.0, (float) VenderBalance::where('user_id', 7)->value('amount'), 0.001);
        $this->assertSame(0, GovernmentLevy::where('booking_id', 'PCL-3002')->count());

        // Second cancel reverses nothing further.
        $flow->cancelWithReversal($parcel->fresh());
        $this->assertEqualsWithDelta(0.0, (float) AdminWallet::query()->first()->balance, 0.001);
        $this->assertEqualsWithDelta(0.0, (float) $campany->balance()->first()->amount, 0.001);
        $this->assertSame(0, GovernmentLevy::count());
    }
}
