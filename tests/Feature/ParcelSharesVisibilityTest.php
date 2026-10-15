<?php

namespace Tests\Feature;

use App\Models\Campany;
use App\Models\Parcel;
use App\Models\Setting;
use App\Models\User;
use App\Models\bus;
use App\Services\ParcelFlowService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Guards the parcel share visibility rules on the shared
 * `parcels.partials.process` partial used by both portals.
 *
 * 1. The bus owner "Assign bus" dropdown only offers buses that accept parcels
 *    (plus the parcel's current bus, so the select never silently switches bus).
 * 2. The bus owner detail card shows ONLY the admin share and the government
 *    levy -- never the vendor's cut nor the bus owner share.
 * 3. The vendor portal keeps its own share + bus owner share + admin + levy.
 *
 * Runs on an in-memory SQLite schema so it never touches the live database.
 */
class ParcelSharesVisibilityTest extends TestCase
{
    private Parcel $parcel;

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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('parcel_commission_percentage', 8, 2)->nullable();
            $table->decimal('parcel_vendor_commission_percentage', 8, 2)->nullable();
            $table->boolean('test_mode')->default(false);
            $table->timestamps();
        });

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

        Schema::create('buses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campany_id')->nullable();
            $table->string('bus_number')->nullable();
            $table->unsignedBigInteger('route_id')->nullable();
            $table->boolean('accept_parcels')->default(true);
            $table->decimal('max_parcel_weight_kg', 10, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bus_id')->nullable();
            $table->string('from')->nullable();
            $table->string('to')->nullable();
            $table->timestamps();
        });

        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bus_id')->nullable();
            $table->unsignedBigInteger('route_id')->nullable();
            $table->string('from')->nullable();
            $table->string('to')->nullable();
            $table->date('schedule_date')->nullable();
            $table->string('start')->nullable();
            $table->timestamps();
        });

        Schema::create('parcels', function (Blueprint $table) {
            $table->id();
            $table->string('parcel_number')->nullable();
            $table->string('parcel_type')->nullable();
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('admin_share', 12, 2)->nullable();
            $table->decimal('vendor_share', 12, 2)->nullable();
            $table->decimal('government_levy', 12, 2)->nullable();
            $table->decimal('owner_share', 12, 2)->nullable();
            $table->string('payment_status')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_mode')->nullable();
            $table->string('payment_ref')->nullable();
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('balance_due', 12, 2)->default(0);
            $table->timestamp('settled_at')->nullable();
            $table->timestamp('deposit_paid_at')->nullable();
            $table->timestamp('balance_paid_at')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('loaded_at')->nullable();
            $table->timestamp('departed_at')->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('collected_at')->nullable();
            $table->string('status')->default('registered');
            $table->unsignedBigInteger('bus_id')->nullable();
            $table->unsignedBigInteger('vender_id')->nullable();
            $table->string('sender_name')->nullable();
            $table->string('sender_contact')->nullable();
            $table->string('receiver_name')->nullable();
            $table->string('receiver_contact_1')->nullable();
            $table->string('receiver_delivery_address')->nullable();
            $table->string('parcel_instructions')->nullable();
            $table->decimal('weight', 10, 2)->nullable();
            $table->decimal('length', 10, 2)->nullable();
            $table->decimal('height', 10, 2)->nullable();
            $table->decimal('width', 10, 2)->nullable();
            $table->text('description')->nullable();
            $table->string('receiving_agent_name')->nullable();
            $table->string('receiving_agent_phone')->nullable();
            $table->string('delivery_rider_name')->nullable();
            $table->string('delivery_rider_phone')->nullable();
            $table->string('conductor_name')->nullable();
            $table->string('conductor_phone')->nullable();
            $table->string('collector_name')->nullable();
            $table->string('collector_phone')->nullable();
            $table->timestamps();
        });
    }

    /**
     * One company with an accepting bus (the parcel's bus) and a refusing bus.
     * 12,000 fee => bus owner 10,800 | vendor 60 | admin 540 | levy 600.
     */
    private function seedWorld(): void
    {
        Setting::create([
            'parcel_commission_percentage' => 5,
            'parcel_vendor_commission_percentage' => 10,
            'test_mode' => false,
        ]);

        $company = Campany::create(['name' => 'Highlink Test Co']);

        $accepting = bus::create([
            'campany_id' => $company->id,
            'bus_number' => 'T-ACCEPT',
            'accept_parcels' => true,
        ]);
        bus::create([
            'campany_id' => $company->id,
            'bus_number' => 'T-REFUSE',
            'accept_parcels' => false,
        ]);

        $this->parcel = Parcel::create([
            'parcel_number' => 'PCL-TEST',
            'parcel_type' => 'Box',
            'bus_id' => $accepting->id,
            'vender_id' => 7,
            'amount_paid' => 12000,
            'admin_share' => 540,
            'vendor_share' => 60,
            'government_levy' => 600,
            'owner_share' => 10800,
            'payment_status' => 'paid',
            'payment_mode' => 'cash',
            'paid_amount' => 12000,
            'balance_due' => 0,
            'status' => 'registered',
        ]);
    }

    private function makeBusOwner(): User
    {
        return User::create([
            'name' => 'Owner',
            'email' => 'owner@example.test',
            'role' => 'local_bus_owner',
            'campany_id' => Campany::query()->first()->id,
        ]);
    }

    /**
     * Render the shared partial under a fake named route so
     * `request()->routeIs('bus_owner.*')` picks the right portal.
     */
    private function render(string $entry): string
    {
        $this->withoutExceptionHandling();
        $this->withoutMiddleware();
        app()->instance('usdToTzs', 2500);
        app()->setLocale('en');

        Route::get('/__parcel-render-'.$entry, function () {
            return view('parcels.partials.process', [
                'parcel' => $this->parcel,
                'flow' => app(ParcelFlowService::class),
                'status' => $this->parcel->status,
                'currency' => 'TZS',
            ]);
        })->name($entry === 'owner' ? 'bus_owner.parcels.show' : 'vender.parcels.show');

        $response = $this->get('/__parcel-render-'.$entry);
        $response->assertOk();

        return $response->getContent();
    }

    private function plain(string $html): string
    {
        return html_entity_decode(strip_tags($html));
    }

    public function test_bus_owner_sees_only_admin_share_and_government_levy(): void
    {
        $this->seedWorld();
        $this->actingAs($this->makeBusOwner());

        $text = $this->plain($this->render('owner'));

        $this->assertStringContainsString("Admin share", $text);
        $this->assertStringContainsString('540.00', $text);
        $this->assertStringContainsString('Government levy', $text);
        $this->assertStringContainsString('600.00', $text);

        $this->assertStringNotContainsString('Vendor share', $text);
        $this->assertStringNotContainsString('Your share', $text);
        $this->assertStringNotContainsString('Bus owner share', $text);
        $this->assertStringNotContainsString('60.00', $text);
        $this->assertStringNotContainsString('10,800.00', $text);
    }

    public function test_bus_owner_assign_dropdown_hides_buses_that_refuse_parcels(): void
    {
        $this->seedWorld();
        $this->actingAs($this->makeBusOwner());

        $html = $this->render('owner');

        $this->assertMatchesRegularExpression('/<option value="\d+"[^>]*>T-ACCEPT[^<]*<\/option>/', $html);
        $this->assertStringContainsString('T-ACCEPT', $html);
        $this->assertStringNotContainsString('T-REFUSE', $html);
    }

    public function test_vendor_still_sees_its_own_share(): void
    {
        $this->seedWorld();

        $text = $this->plain($this->render('vendor'));

        $this->assertStringContainsString('Your share', $text);
        $this->assertStringContainsString('60.00', $text);
        $this->assertStringContainsString('Bus owner share', $text);
        $this->assertStringContainsString('10,800.00', $text);
        $this->assertStringContainsString('Admin share', $text);
        $this->assertStringContainsString('Government levy', $text);
    }
}
