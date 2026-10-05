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

    public function test_vendor_cannot_cancel_a_parcel_already_in_bus_owner_custody(): void
    {
        $flow = new ParcelFlowService();

        foreach (ParcelFlowService::custodyStages() as $stage) {
            $this->assertFalse(
                $flow->actorMayCancel($stage, false),
                "Vendor must not cancel a parcel in the '{$stage}' stage"
            );
        }
    }

    public function test_in_custody_only_after_handover_stages(): void
    {
        $flow = new ParcelFlowService();

        foreach ([
            ParcelFlowService::STATUS_IN_STORE,
            ParcelFlowService::STATUS_LOADED,
            ParcelFlowService::STATUS_RECEIVED,
        ] as $stage) {
            $this->assertTrue($flow->inCustody($stage), "'{$stage}' should be in custody");
        }

        foreach ([
            ParcelFlowService::STATUS_AWAITING_PAYMENT,
            ParcelFlowService::STATUS_REGISTERED,
            ParcelFlowService::STATUS_PENDING,
            ParcelFlowService::STATUS_COMPLETED,
            ParcelFlowService::STATUS_CANCELLED,
        ] as $stage) {
            $this->assertFalse($flow->inCustody($stage), "'{$stage}' should not be in custody");
        }
    }

    public function test_vendor_may_cancel_before_handover(): void
    {
        $flow = new ParcelFlowService();

        $this->assertTrue($flow->actorMayCancel(ParcelFlowService::STATUS_AWAITING_PAYMENT, false));
        $this->assertTrue($flow->actorMayCancel(ParcelFlowService::STATUS_REGISTERED, false));
        $this->assertTrue($flow->actorMayCancel(ParcelFlowService::STATUS_PENDING, false));
    }

    public function test_bus_owner_may_cancel_until_completed(): void
    {
        $flow = new ParcelFlowService();

        foreach ([
            ParcelFlowService::STATUS_AWAITING_PAYMENT,
            ParcelFlowService::STATUS_REGISTERED,
            ParcelFlowService::STATUS_IN_STORE,
            ParcelFlowService::STATUS_LOADED,
            ParcelFlowService::STATUS_RECEIVED,
        ] as $stage) {
            $this->assertTrue($flow->actorMayCancel($stage, true), "Bus owner should cancel '{$stage}'");
        }

        // Nobody may cancel a completed parcel.
        $this->assertFalse($flow->actorMayCancel(ParcelFlowService::STATUS_COMPLETED, true));
        $this->assertFalse($flow->actorMayCancel(ParcelFlowService::STATUS_COMPLETED, false));
    }
}
