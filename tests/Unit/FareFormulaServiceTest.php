<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Services\FareFormulaService;
use PHPUnit\Framework\TestCase;

class FareFormulaServiceTest extends TestCase
{
    public function test_it_uses_sheet_defaults_when_settings_missing(): void
    {
        $service = new FareFormulaService();
        $result = $service->calculateSettlement(
            12000,
            10000,
            0,
            0,
            null,
            null,
            null
        );

        $this->assertEquals(500.0, $result['government_levy_on_fare']);
        // 5% of levy-inclusive bus fare + commission adding (0 without company)
        $this->assertEquals(500.0, $result['system_commission_total']);
        // 2% of levy-inclusive + 100
        $this->assertEquals(300.0, $result['service_fees']);
        $this->assertEquals(9000.0, $result['bus_owner_share']);
    }

    public function test_it_applies_setting_and_vendor_rates(): void
    {
        $service = new FareFormulaService();
        $setting = new Setting();
        $setting->service_percentage = 4;
        $setting->service = 250;

        $result = $service->calculateSettlement(
            20000,
            15000,
            0,
            0,
            $setting,
            null,
            8
        );

        $this->assertEquals(750.0, $result['government_levy_on_fare']);
        $this->assertEquals(750.0, $result['system_commission_total']);
        $this->assertEquals(850.0, $result['service_fees']);
        $this->assertEquals(60.0, round($result['commission_to_vendor'], 2));
    }

    public function test_commission_amount_is_adding_not_override(): void
    {
        $service = new FareFormulaService();
        $setting = new Setting();
        $setting->service_percentage = 2;
        $setting->service = 100;

        $company = new \App\Models\Campany();
        $company->percentage = 5;
        $company->commission_amount = 50;

        $result = $service->calculateSettlement(
            1000,
            1000,
            0,
            0,
            $setting,
            $company,
            10
        );

        // 5% of 1000 + 50 adding = 100
        $this->assertEquals(100.0, $result['system_commission_total']);
    }

    public function test_traveller_service_fee_uses_displayed_seat_fare(): void
    {
        $service = new FareFormulaService();
        $setting = new Setting();
        $setting->service_percentage = 2;
        $setting->service = 100;

        // 1 seat at 1000: (1000×2%) + (100×1) = 20 + 100 = 120 → payable 1120
        $fee = $service->calculateTravellerServiceFee(1000.0, $setting, 1);
        $this->assertEquals(120.0, $fee);
    }

    public function test_traveller_service_fee_scales_per_seat(): void
    {
        $service = new FareFormulaService();
        $setting = new Setting();
        $setting->service_percentage = 2;
        $setting->service = 100;

        // 1 seat at 2000: (2000×2%) + (100×1) = 40 + 100 = 140
        $this->assertEquals(140.0, $service->calculateTravellerServiceFee(2000.0, $setting, 1));

        // 2 seats at 2000 each (total 4000): (4000×2%) + (100×2) = 80 + 200 = 280
        $this->assertEquals(280.0, $service->calculateTravellerServiceFee(4000.0, $setting, 2));
    }

    public function test_settlement_service_fees_match_traveller_fee_for_multi_seat(): void
    {
        $service = new FareFormulaService();
        $setting = new Setting();
        $setting->service_percentage = 2;
        $setting->service = 100;

        // 2 seats × 1000 = 2000 total: (2000×2%) + (100×2) = 240
        $busFare = 2000.0;
        $travellerFee = $service->calculateTravellerServiceFee($busFare, $setting, 2);
        $result = $service->calculateSettlement($busFare, $busFare, 0, 0, $setting, null, null, 2);

        $this->assertEquals(240.0, $travellerFee);
        $this->assertEquals($travellerFee, $result['service_fees']);
        $this->assertEquals(12.0, $result['government_levy_on_service_fee']);
        $this->assertEquals(100.0, $result['system_commission_total']);
    }

    /**
     * Sheet B23: government levy on the service fee is 5% of the FULL service fee (B16).
     * A vendor on the booking takes its cut out of the system's share, never out of
     * what is owed to the government, so the levy must not move with the vendor rate.
     */
    public function test_government_levy_on_service_fee_ignores_vendor_share(): void
    {
        $service = new FareFormulaService();
        $setting = new Setting();
        $setting->service_percentage = 2;
        $setting->service = 100;

        $withoutVendor = $service->calculateSettlement(2000.0, 2000.0, 0, 0, $setting, null, null, 2);
        $withVendor = $service->calculateSettlement(2000.0, 2000.0, 0, 0, $setting, null, 10.0, 2);

        // Same service fee base, so the same levy regardless of the vendor.
        $this->assertEquals(240.0, $withVendor['service_fees']);
        $this->assertEquals(12.0, $withVendor['government_levy_on_service_fee']);
        $this->assertEquals(
            $withoutVendor['government_levy_on_service_fee'],
            $withVendor['government_levy_on_service_fee']
        );

        // The vendor's 10% comes out of the system's retained share, not the levy.
        $this->assertEquals(24.0, $withVendor['service_fees_to_vendor']);
        $this->assertEquals(204.0, $withVendor['system_service_fee_share']);
        $this->assertEquals(228.0, $withoutVendor['system_service_fee_share']);

        // Vendor + government + system must account for the whole service fee.
        $this->assertEquals(
            240.0,
            round(
                $withVendor['service_fees_to_vendor']
                + $withVendor['government_levy_on_service_fee']
                + $withVendor['system_service_fee_share'],
                2
            )
        );
    }

    public function test_total_government_levies_is_fare_plus_service_levy(): void
    {
        $service = new FareFormulaService();
        $setting = new Setting();
        $setting->service_percentage = 2;
        $setting->service = 100;

        $result = $service->calculateSettlement(2000.0, 2000.0, 0, 0, $setting, null, 10.0, 2);

        // Sheet B24 = B21 + B23.
        $this->assertEquals(100.0, $result['government_levy_on_fare']);
        $this->assertEquals(112.0, $result['total_government_levies']);
    }
}
