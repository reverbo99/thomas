<?php

namespace Tests\Feature;

use App\Http\Controllers\AdminController;
use App\Models\Booking;
use App\Models\Campany;
use App\Models\User;
use App\Models\bus;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Guards the withdrawable-balance rule on the bus-owner earnings page.
 *
 * The company wallet is credited when a booking is paid, so it also holds revenue
 * for trips that have not departed yet. Only trips up to and including today may be
 * requested; later trips stay locked until their travel date.
 *
 * Runs on an in-memory SQLite schema so it never touches the live database.
 */
class BusOwnerWalletSummaryTest extends TestCase
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

        $this->createSchema();
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('role')->nullable();
            $table->unsignedBigInteger('campany_id')->nullable();
            $table->timestamps();
        });

        Schema::create('campanies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campany_id')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('fees', 15, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('buses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campany_id')->nullable();
            $table->string('bus_number')->nullable();
            $table->timestamps();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code')->nullable();
            $table->unsignedBigInteger('campany_id')->nullable();
            $table->unsignedBigInteger('bus_id')->nullable();
            $table->date('travel_date')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('customer_paid_total', 15, 2)->nullable();
            $table->string('payment_status')->nullable();
            $table->string('booking_channel')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('luggage_status')->nullable();
            $table->boolean('has_excess_luggage')->default(false);
            $table->decimal('excess_luggage_fee', 15, 2)->default(0);
            $table->unsignedBigInteger('vender_id')->nullable();
            $table->timestamps();
        });

        Schema::create('excess_luggage_escrow', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->string('status')->nullable();
            $table->decimal('actual_fee', 15, 2)->nullable();
            $table->decimal('released_fee', 15, 2)->nullable();
            $table->decimal('actual_weight', 10, 2)->nullable();
            $table->decimal('owner_share', 15, 2)->nullable();
            $table->decimal('held_amount', 15, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * @return array{0: User, 1: Campany, 2: bus}
     */
    private function makeBusOwner(float $walletBalance): array
    {
        $user = User::create([
            'name' => 'Bus Owner',
            'email' => 'owner@example.test',
            'role' => 'bus_campany',
        ]);

        $company = Campany::create([
            'name' => 'Owner Coaches',
            'user_id' => $user->id,
        ]);

        $user->forceFill(['campany_id' => $company->id])->save();

        DB::table('balances')->insert([
            'campany_id' => $company->id,
            'amount' => $walletBalance,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $bus = bus::create([
            'campany_id' => $company->id,
            'bus_number' => 'T123',
        ]);

        return [$user->fresh(), $company, $bus];
    }

    private function addPaidBooking(Campany $company, bus $bus, Carbon $travelDate, float $ownerShare, string $status = 'Paid'): Booking
    {
        return Booking::create([
            'booking_code' => 'BK-' . uniqid(),
            'campany_id' => $company->id,
            'bus_id' => $bus->id,
            'travel_date' => $travelDate->toDateString(),
            'amount' => $ownerShare,
            'customer_paid_total' => $ownerShare,
            'payment_status' => $status,
            'has_excess_luggage' => false,
            'excess_luggage_fee' => 0,
        ]);
    }

    private function walletSummary(User $user): array
    {
        $controller = new AdminController();
        $method = new ReflectionMethod($controller, 'busOwnerWalletSummary');
        $method->setAccessible(true);

        return $method->invoke($controller, $user);
    }

    public function test_today_and_past_trips_are_withdrawable_while_future_trips_stay_locked(): void
    {
        [$user, $company, $bus] = $this->makeBusOwner(350);

        $this->addPaidBooking($company, $bus, Carbon::today()->subDays(3), 100);
        $this->addPaidBooking($company, $bus, Carbon::today(), 50);
        $this->addPaidBooking($company, $bus, Carbon::today()->addDays(4), 200);

        $summary = $this->walletSummary($user);

        $this->assertSame(350.0, $summary['balance']);
        $this->assertSame(150.0, $summary['withdrawable'], 'Past and today trips are withdrawable.');
        $this->assertSame(200.0, $summary['locked'], 'Trips after today stay locked.');
    }

    public function test_unpaid_future_bookings_do_not_lock_wallet_money(): void
    {
        [$user, $company, $bus] = $this->makeBusOwner(100);

        $this->addPaidBooking($company, $bus, Carbon::today()->addDays(2), 500, 'Pending');

        $summary = $this->walletSummary($user);

        $this->assertSame(0.0, $summary['locked']);
        $this->assertSame(100.0, $summary['withdrawable']);
    }

    public function test_locked_amount_never_exceeds_the_wallet_balance(): void
    {
        // Reversals/cancellations can drain the wallet below future-trip revenue.
        [$user, $company, $bus] = $this->makeBusOwner(120);

        $this->addPaidBooking($company, $bus, Carbon::today()->addDays(3), 200);

        $summary = $this->walletSummary($user);

        $this->assertSame(120.0, $summary['locked']);
        $this->assertSame(0.0, $summary['withdrawable']);
    }

    public function test_wallet_balance_is_split_across_withdrawable_and_locked(): void
    {
        [$user, $company, $bus] = $this->makeBusOwner(900);

        $this->addPaidBooking($company, $bus, Carbon::today()->subDay(), 400);
        $this->addPaidBooking($company, $bus, Carbon::today()->addDays(10), 500);

        $summary = $this->walletSummary($user);

        $this->assertSame(
            $summary['balance'],
            $summary['withdrawable'] + $summary['locked'],
            'Balance, withdrawable and locked must reconcile.'
        );
    }
}
