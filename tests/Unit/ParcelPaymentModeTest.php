<?php

namespace Tests\Unit;

use App\Models\Parcel;
use App\Services\ParcelFlowService;
use PHPUnit\Framework\TestCase;

class ParcelPaymentModeTest extends TestCase
{
    public function test_payment_mode_constants_exist(): void
    {
        $this->assertSame('cash', ParcelFlowService::MODE_CASH);
        $this->assertSame('instalment', ParcelFlowService::MODE_INSTALMENT);
        $this->assertSame('cod', ParcelFlowService::MODE_COD);
    }

    public function test_partial_and_cod_payment_status_constants_exist(): void
    {
        $this->assertSame('partial', ParcelFlowService::PAY_PARTIAL);
        $this->assertSame('cod', ParcelFlowService::PAY_COD);
    }

    public function test_is_fully_paid_only_for_paid_status(): void
    {
        $flow = new ParcelFlowService();

        $this->assertTrue($flow->isFullyPaid(new Parcel(['payment_status' => 'paid'])));
        $this->assertFalse($flow->isFullyPaid(new Parcel(['payment_status' => 'partial'])));
        $this->assertFalse($flow->isFullyPaid(new Parcel(['payment_status' => 'cod'])));
        $this->assertFalse($flow->isFullyPaid(new Parcel(['payment_status' => 'unpaid'])));
        $this->assertFalse($flow->isFullyPaid(null));
    }

    public function test_payment_allows_movement_for_paid_partial_and_cod(): void
    {
        $flow = new ParcelFlowService();

        foreach ([
            ParcelFlowService::PAY_PAID,
            ParcelFlowService::PAY_PARTIAL,
            ParcelFlowService::PAY_COD,
        ] as $status) {
            $this->assertTrue(
                $flow->paymentAllowsMovement(new Parcel(['payment_status' => $status])),
                "Movement should be allowed for '{$status}'"
            );
        }
    }

    public function test_payment_blocks_movement_for_unpaid_and_pending(): void
    {
        $flow = new ParcelFlowService();

        foreach ([
            ParcelFlowService::PAY_UNPAID,
            ParcelFlowService::PAY_PENDING,
            null,
        ] as $status) {
            $this->assertFalse(
                $flow->paymentAllowsMovement(new Parcel(['payment_status' => $status])),
                'Movement must be blocked for ' . var_export($status, true)
            );
        }

        $this->assertFalse($flow->paymentAllowsMovement(null));
    }

    public function test_payment_mode_columns_are_mass_assignable(): void
    {
        $fillable = (new Parcel())->getFillable();

        foreach (['payment_mode', 'paid_amount', 'balance_due', 'deposit_paid_at', 'balance_paid_at'] as $field) {
            $this->assertContains($field, $fillable, "Parcel::\$fillable is missing '{$field}'");
        }
    }
}
