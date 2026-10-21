<?php

namespace Tests\Feature;

use App\Models\bus;
use App\Models\Campany;
use App\Models\Parcel;
use App\Models\route;
use App\Models\User;
use App\Services\Sms\ParcelSmsTemplates;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Guards the two Swahili parcel SMS templates:
 *  1. "mzigo unapotumwa" -> sender, when the origin office accepts the parcel.
 *  2. "mzigo unapopokelewa kwa maelekezo ya collection" -> receiver on arrival.
 *
 * Runs on an in-memory SQLite schema so it never touches the live database.
 */
class ParcelSmsTemplatesTest extends TestCase
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

        Carbon::setTestNow(Carbon::parse('2026-10-20 14:35:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('contact')->nullable();
            $table->string('role')->nullable();
            $table->timestamps();
        });

        Schema::create('campanies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('payment_number')->nullable();
            $table->timestamps();
        });

        Schema::create('bus_owner_account', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campany_id')->nullable();
            $table->string('office_number')->nullable();
            $table->string('box')->nullable();
            $table->string('street')->nullable();
            $table->string('town')->nullable();
            $table->string('city')->nullable();
            $table->timestamps();
        });

        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bus_id')->nullable();
            $table->string('from')->nullable();
            $table->string('to')->nullable();
            $table->timestamps();
        });

        Schema::create('buses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campany_id')->nullable();
            $table->unsignedBigInteger('route_id')->nullable();
            $table->string('bus_number')->nullable();
            $table->string('customer_service_name_1')->nullable();
            $table->string('customer_service_contact_1')->nullable();
            $table->string('customer_service_contact_2')->nullable();
            $table->timestamps();
        });

        Schema::create('vender_account', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('house_number')->nullable();
            $table->string('street')->nullable();
            $table->string('town')->nullable();
            $table->string('city')->nullable();
            $table->string('altenative_number')->nullable();
            $table->timestamps();
        });

        Schema::create('parcels', function (Blueprint $table) {
            $table->id();
            $table->string('parcel_number')->nullable();
            $table->string('parcel_type')->nullable();
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->unsignedBigInteger('bus_id')->nullable();
            $table->unsignedBigInteger('vender_id')->nullable();
            $table->string('sender_name')->nullable();
            $table->string('sender_contact')->nullable();
            $table->string('parcel_instructions')->nullable();
            $table->string('receiver_name')->nullable();
            $table->string('receiver_contact_1')->nullable();
            $table->string('receiver_delivery_address')->nullable();
            $table->string('receiving_agent_name')->nullable();
            $table->string('receiving_agent_phone')->nullable();
            $table->string('status')->default('registered');
            $table->timestamps();
        });
    }

    private function makeCompany(): array
    {
        $owner = User::create([
            'name' => 'Thomas Express',
            'email' => 'owner@example.test',
            'role' => 'bus_campany',
        ]);

        $company = Campany::create([
            'name' => 'Thomas Express',
            'user_id' => $owner->id,
            'payment_number' => '0715020945',
        ]);

        DB::table('bus_owner_account')->insert([
            'campany_id' => $company->id,
            'office_number' => '0765553953',
            'street' => 'Ubungo',
            'town' => 'Dar Es Salaam',
            'city' => 'Dar Es Salaam',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $route = route::create([
            'from' => 'Dar es Salaam',
            'to' => 'Dodoma',
        ]);

        $bus = bus::create([
            'campany_id' => $company->id,
            'route_id' => $route->id,
            'bus_number' => 'T 777 EMM',
            'customer_service_name_1' => 'Stella Yohana',
            'customer_service_contact_1' => '0684123433',
        ]);

        // bus::route() resolves the route through routes.bus_id.
        $route->update(['bus_id' => $bus->id]);

        return [$company, $bus];
    }

    private function makeParcel(bus $bus, ?User $vendor = null): Parcel
    {
        return Parcel::create([
            'parcel_number' => 'PCL-ABC123',
            'parcel_type' => 'Box',
            'amount_paid' => 20000,
            'bus_id' => $bus->id,
            'vender_id' => $vendor?->id,
            'sender_name' => 'Joseph Mpondomoko',
            'sender_contact' => '+255715020945',
            'parcel_instructions' => 'collection',
            'receiver_name' => 'Masanja Ntebela',
            'receiver_contact_1' => '+255765553953',
            'receiver_delivery_address' => 'Thomas Express office, Dodoma.',
            'status' => 'registered',
        ]);
    }

    public function test_sent_template_tells_the_sender_who_where_and_when_the_parcel_was_accepted(): void
    {
        [$company, $bus] = $this->makeCompany();
        $parcel = $this->makeParcel($bus);

        $message = ParcelSmsTemplates::sent($parcel);

        $this->assertStringContainsString('Ndugu Joseph Mpondomoko', $message);
        $this->assertStringContainsString('kutoka Dar es Salaam kwenda Dodoma', $message);
        $this->assertStringContainsString('kwa Masanja Ntebela', $message);
        $this->assertStringContainsString('umepokelewa na Thomas Express', $message);
        $this->assertStringContainsString('ofisi ya Ubungo, Dar Es Salaam', $message);
        $this->assertStringContainsString('simu 0765553953', $message);
        $this->assertStringContainsString('Leo tarehe 20/10/2026 saa 14:35', $message);
        $this->assertStringContainsString('nambari PCL-ABC123', $message);
        $this->assertStringContainsString('Stella Yohana 0684123433', $message);
        $this->assertStringNotContainsString('()', $message);
    }

    public function test_sent_template_uses_the_vendor_office_when_an_agent_registered_the_parcel(): void
    {
        [$company, $bus] = $this->makeCompany();

        $vendor = User::create([
            'name' => 'Francis Oswald',
            'email' => 'vendor@example.test',
            'phone' => '0749343101',
            'role' => 'vender',
        ]);
        DB::table('vender_account')->insert([
            'user_id' => $vendor->id,
            'house_number' => '56',
            'street' => 'Shekilango street',
            'town' => 'Ubungo',
            'city' => 'Dar es Salaam',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $message = ParcelSmsTemplates::sent($this->makeParcel($bus, $vendor));

        $this->assertStringContainsString('56 Shekilango street, Ubungo, Dar es Salaam', $message);
        $this->assertStringContainsString('simu 0749343101', $message);
    }

    public function test_collection_template_tells_the_receiver_where_to_collect(): void
    {
        [$company, $bus] = $this->makeCompany();
        $parcel = $this->makeParcel($bus);

        $message = ParcelSmsTemplates::arrivedForCollection($parcel);

        $this->assertStringContainsString('Ndugu Masanja Ntebela', $message);
        $this->assertStringContainsString('mzigo namba PCL-ABC123', $message);
        $this->assertStringContainsString('uliotumiwa na Joseph Mpondomoko', $message);
        $this->assertStringContainsString('umewasili Dodoma', $message);
        $this->assertStringContainsString('katika ofisi za Thomas Express zilizopo Dodoma', $message);
        $this->assertStringContainsString('simu 0765553953', $message);
        $this->assertStringContainsString('Stella Yohana 0684123433', $message);
    }

    public function test_templates_stay_readable_when_the_office_profile_is_missing(): void
    {
        $owner = User::create([
            'name' => 'Bare Owner',
            'email' => 'bare@example.test',
            'role' => 'bus_campany',
        ]);
        $company = Campany::create(['name' => 'Bare Coaches', 'user_id' => $owner->id]);
        $bus = bus::create(['campany_id' => $company->id, 'bus_number' => 'T1']);

        $parcel = $this->makeParcel($bus);

        $sent = ParcelSmsTemplates::sent($parcel);
        $collection = ParcelSmsTemplates::arrivedForCollection($parcel);

        $this->assertStringContainsString('umepokelewa na Bare Coaches', $sent);
        $this->assertStringContainsString('Ndugu Joseph Mpondomoko', $sent);
        $this->assertStringContainsString('PCL-ABC123', $sent);
        $this->assertStringContainsString('katika ofisi za Bare Coaches', $collection);
        $this->assertStringNotContainsString('()', $sent);
        $this->assertStringNotContainsString('()', $collection);
        $this->assertStringNotContainsString(', ,', $sent);
        $this->assertStringNotContainsString(', .', $sent);
    }
}
