<?php

namespace Tests\Unit;

use App\Models\bus;
use App\Models\Parcel;
use App\Services\ParcelFlowService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Bus parcel capacity: only parcels still on the bus count.
 *
 * A parcel that reached the destination office (`received`/legacy `arrived`),
 * was collected (`completed`), cancelled, or is still awaiting payment must not
 * keep occupying bus space.
 */
class ParcelCapacityTest extends TestCase
{
    private bus $bus;

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

        Schema::create('buses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campany_id')->nullable();
            $table->string('bus_number')->nullable();
            $table->boolean('accept_parcels')->default(false);
            $table->decimal('max_parcel_weight_kg', 10, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('parcels', function (Blueprint $table) {
            $table->id();
            $table->string('parcel_number')->nullable();
            $table->unsignedBigInteger('bus_id')->nullable();
            $table->decimal('weight', 10, 2)->nullable();
            $table->string('status')->nullable();
            $table->string('payment_status')->nullable();
            $table->timestamps();
        });

        $this->bus = bus::create([
            'campany_id' => 1,
            'bus_number' => 'T-CAP',
            'accept_parcels' => true,
            'max_parcel_weight_kg' => 10,
        ]);
    }

    private function parcel(string $status, float $weight, string $paymentStatus = ParcelFlowService::PAY_PAID): Parcel
    {
        return Parcel::create([
            'parcel_number' => 'PCL-' . uniqid(),
            'bus_id' => $this->bus->id,
            'weight' => $weight,
            'status' => $status,
            'payment_status' => $paymentStatus,
        ]);
    }

    public function test_received_parcel_frees_capacity(): void
    {
        // 10 kg already delivered at the destination office => bus is empty again.
        $this->parcel(ParcelFlowService::STATUS_RECEIVED, 10);

        $this->assertSame(0.0, (float) app(ParcelFlowService::class)->activeLoadQuery($this->bus->id)->sum('weight'));

        // A new 5 kg parcel now fits (it used to fail with "used 10.00 / max 10.00").
        app(ParcelFlowService::class)->assertCapacity($this->bus, 5.0);

        $this->assertTrue(true);
    }

    public function test_collected_cancelled_and_unpaid_parcels_do_not_count(): void
    {
        $this->parcel(ParcelFlowService::STATUS_COMPLETED, 10);
        $this->parcel(ParcelFlowService::STATUS_CANCELLED, 10);
        $this->parcel(ParcelFlowService::STATUS_AWAITING_PAYMENT, 10, ParcelFlowService::PAY_UNPAID);

        app(ParcelFlowService::class)->assertCapacity($this->bus, 10.0);

        $this->assertSame(0.0, (float) app(ParcelFlowService::class)->activeLoadQuery($this->bus->id)->sum('weight'));
    }

    public function test_parcel_still_on_the_bus_blocks_capacity(): void
    {
        $this->parcel(ParcelFlowService::STATUS_LOADED, 8);

        app(ParcelFlowService::class)->assertCapacity($this->bus, 1.0);
        $this->expectException(\RuntimeException::class);

        app(ParcelFlowService::class)->assertCapacity($this->bus, 5.0);
    }

    public function test_bus_without_a_capacity_limit_is_never_blocked(): void
    {
        $this->bus->update(['max_parcel_weight_kg' => null]);
        $this->parcel(ParcelFlowService::STATUS_LOADED, 5000);

        app(ParcelFlowService::class)->assertCapacity($this->bus->fresh(), 5000.0);

        $this->assertTrue(true);
    }

    public function test_parcel_acceptance_is_opt_in(): void
    {
        $flow = app(ParcelFlowService::class);

        $this->bus->update(['accept_parcels' => false]);
        try {
            $flow->assertBusAcceptsParcels($this->bus->fresh());
            $this->fail('a bus that has not been enabled must reject parcels');
        } catch (\RuntimeException $e) {
            $this->assertSame(__('vender/parcels.bus_not_accepting'), $e->getMessage());
        }

        $this->bus->update(['accept_parcels' => true]);
        $flow->assertBusAcceptsParcels($this->bus->fresh());
        $this->assertTrue(true);
    }
}
