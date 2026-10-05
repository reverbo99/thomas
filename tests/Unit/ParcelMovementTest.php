<?php

namespace Tests\Unit;

use App\Models\Parcel;
use App\Services\ParcelFlowService;
use PHPUnit\Framework\TestCase;

class ParcelMovementTest extends TestCase
{
    public function test_movement_stage_constants_exist(): void
    {
        $this->assertSame('in_store', ParcelFlowService::STATUS_IN_STORE);
        $this->assertSame('loaded', ParcelFlowService::STATUS_LOADED);
        $this->assertSame('received', ParcelFlowService::STATUS_RECEIVED);
        $this->assertSame('completed', ParcelFlowService::STATUS_COMPLETED);
    }

    public function test_legacy_pending_normalizes_on_payment_status(): void
    {
        $flow = new ParcelFlowService();

        $this->assertSame(
            ParcelFlowService::STATUS_REGISTERED,
            $flow->normalizeStatus(new Parcel(['status' => 'pending', 'payment_status' => 'paid']))
        );

        $this->assertSame(
            ParcelFlowService::STATUS_AWAITING_PAYMENT,
            $flow->normalizeStatus(new Parcel(['status' => 'pending', 'payment_status' => 'unpaid']))
        );
    }
}
