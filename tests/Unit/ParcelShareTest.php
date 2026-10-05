<?php

namespace Tests\Unit;

use App\Services\ParcelFlowService;
use PHPUnit\Framework\TestCase;

class ParcelShareTest extends TestCase
{
    public function test_vendor_share_is_ten_percent_of_five_percent(): void
    {
        $split = ParcelFlowService::splitAmounts(35000, true, 5, 10);

        $this->assertSame(1750.0, $split['pool']);
        $this->assertSame(175.0, $split['vendor']);
        $this->assertSame(1575.0, $split['admin']);
        $this->assertSame(1750.0, $split['government']);
        $this->assertSame(31500.0, $split['owner']);
    }

    public function test_parcel_without_vendor_keeps_the_full_commission_pool_for_admin(): void
    {
        $split = ParcelFlowService::splitAmounts(10000, false, 5, 10);

        $this->assertSame(0.0, $split['vendor']);
        $this->assertSame(500.0, $split['admin']);
        $this->assertSame(500.0, $split['government']);
        $this->assertSame(9000.0, $split['owner']);
    }

    public function test_settled_parcel_reports_stored_admin_share_for_combined_income(): void
    {
        // System Income's "Combined total" sums parcel_admin_share(), which reads the
        // share stored at settlement — not a fresh percentage of the fee.
        $parcel = (object) [
            'amount_paid' => 10000,
            'vender_id' => null,
            'owner_share' => 9000,
            'admin_share' => 500,
            'vendor_share' => 0,
            'government_levy' => 500,
            'payment_status' => 'paid',
        ];

        $split = ParcelFlowService::splitForParcel($parcel);

        $this->assertSame(500.0, $split['admin']);
        $this->assertSame(0.0, $split['vendor']);
        $this->assertSame(500.0, $split['government']);
        $this->assertSame(9000.0, $split['owner']);
    }
}
