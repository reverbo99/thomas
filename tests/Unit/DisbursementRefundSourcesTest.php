<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\Disbursement;
use App\Models\DisbursementItem;
use App\Models\ExcessLuggageEscrow;
use App\Models\Refund;
use App\Models\SpecialHireOrder;
use App\Services\DisbursementService;
use App\Services\ExcessLuggageService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Guards the promise of the disbursements page: every refund the system owes
 * shows up in the daily batch, including refunds that were approved outside the
 * batch (luggage admin approval / special-hire operator refund) and refunds
 * created after today's batch had already been collected.
 *
 * Runs on an in-memory SQLite schema so it never touches the live database.
 */
class DisbursementRefundSourcesTest extends TestCase
{
    private DisbursementService $service;

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

        $this->service = app(DisbursementService::class);
    }

    public function test_every_owed_refund_source_lands_in_the_daily_batch(): void
    {
        $this->makeBookingRefund([
            'booking_code' => 'BK-RFD',
            'amount' => 50000,
            'status' => 'Pending',
            'phone' => '0712345678',
        ]);

        $this->makeLuggageBooking([
            'booking_code' => 'BK-XLG',
            'luggage_payment_status' => ExcessLuggageService::PAYMENT_REFUND_PENDING,
            'luggage_refund_amount' => -20000,
        ]);

        $this->makeHireOrder([
            'order_code' => 'SH-RFD',
            'payment_status' => 'refund_pending',
            'deposit_amount' => 120000,
            'deposit_paid_at' => now(),
            'total_amount' => 120000,
        ]);

        $batch = $this->service->collectForDate(today());

        $items = $batch->items()->orderBy('id')->get();

        $this->assertSame(3, $items->count(), 'All three refund sources must be batched.');
        $this->assertSame(
            [DisbursementItem::SOURCE_BOOKING_REFUND, DisbursementItem::SOURCE_EXCESS_LUGGAGE, DisbursementItem::SOURCE_SPECIAL_HIRE_REFUND],
            $items->pluck('source_type')->all()
        );
        $this->assertSame([50000.0, 20000.0, 120000.0], $items->pluck('amount')->map(fn ($v) => (float) $v)->all());
    }

    public function test_refunds_already_marked_refunded_but_never_batched_are_caught_up(): void
    {
        // Historic admin approval / operator action marked these "refunded"
        // without creating a disbursement item. They are still owed.
        $this->makeLuggageBooking([
            'booking_code' => 'BK-OLD-XLG',
            'luggage_payment_status' => ExcessLuggageService::PAYMENT_REFUNDED,
            'luggage_refund_amount' => -15000,
        ]);

        $this->makeHireOrder([
            'order_code' => 'SH-OLD-RFD',
            'payment_status' => 'refunded',
            'deposit_amount' => 80000,
            'deposit_paid_at' => now(),
            'total_amount' => 80000,
        ]);

        $batch = $this->service->collectForDate(today());
        $items = $batch->items()->orderBy('id')->get();

        $this->assertSame(2, $items->count());
        $this->assertSame(
            [DisbursementItem::SOURCE_EXCESS_LUGGAGE, DisbursementItem::SOURCE_SPECIAL_HIRE_REFUND],
            $items->pluck('source_type')->all()
        );
    }

    public function test_today_batch_is_topped_up_and_collection_is_idempotent(): void
    {
        $this->makeBookingRefund([
            'booking_code' => 'BK-FIRST',
            'amount' => 10000,
            'status' => 'Approved',
            'phone' => '0712345678',
        ]);

        $batch = $this->service->collectForDate(today());
        $this->assertSame(1, $batch->items()->count());

        // A refund requested after today's batch was collected must still show
        // up on today's page instead of waiting for tomorrow's batch.
        $this->makeBookingRefund([
            'booking_code' => 'BK-SECOND',
            'amount' => 25000,
            'status' => 'Pending',
            'phone' => '0712345678',
        ]);

        $batch = $this->service->collectForDate(today());
        $this->assertSame(2, $batch->items()->count());
        $this->assertSame($batch->id, Disbursement::query()->whereDate('batch_date', today()->toDateString())->value('id'));

        // Collecting repeatedly must never duplicate an obligation.
        $this->service->collectForDate(today());
        $this->assertSame(2, $batch->items()->count());
    }

    public function test_admin_approval_queues_payout_instead_of_marking_refund_paid(): void
    {
        $booking = $this->makeLuggageBooking([
            'booking_code' => 'BK-APPROVE',
            'luggage_payment_status' => ExcessLuggageService::PAYMENT_REFUND_PENDING,
            'luggage_refund_amount' => -30000,
        ]);

        ExcessLuggageEscrow::create([
            'booking_id' => $booking->id,
            'booking_code' => $booking->booking_code,
            'held_amount' => 30000,
            'surplus_amount' => 30000,
            'status' => ExcessLuggageEscrow::STATUS_REFUND_PENDING,
        ]);

        $item = $this->service->approveExcessLuggageRefund($booking);

        $this->assertSame(DisbursementItem::SOURCE_EXCESS_LUGGAGE, $item->source_type);
        $this->assertSame(DisbursementItem::STATUS_PENDING, $item->status);
        $this->assertSame(30000.0, (float) $item->amount);

        // Approval alone must not finalise the source record.
        $this->assertSame(
            ExcessLuggageService::PAYMENT_REFUND_PENDING,
            $booking->fresh()->luggage_payment_status
        );
        $this->assertNotNull(
            ExcessLuggageEscrow::query()->where('booking_id', $booking->id)->value('refund_approved_at')
        );
    }

    public function test_rejecting_a_refund_skips_an_already_batched_payout(): void
    {
        $refund = $this->makeBookingRefund([
            'booking_code' => 'BK-REJECT',
            'amount' => 40000,
            'status' => 'Pending',
            'phone' => '0712345678',
        ]);

        $this->service->collectForDate(today());

        $item = DisbursementItem::query()
            ->where('source_type', DisbursementItem::SOURCE_BOOKING_REFUND)
            ->where('source_id', $refund->id)
            ->firstOrFail();

        $cancelled = $this->service->cancelPendingItems(
            DisbursementItem::SOURCE_BOOKING_REFUND,
            $refund->id,
            'Refund request rejected.'
        );

        $this->assertSame(1, $cancelled);
        $this->assertSame(DisbursementItem::STATUS_SKIPPED, $item->fresh()->status);
    }

    public function test_reapproving_a_refund_reopens_a_skipped_payout(): void
    {
        $booking = $this->makeLuggageBooking([
            'booking_code' => 'BK-REOPEN',
            'luggage_payment_status' => ExcessLuggageService::PAYMENT_REFUND_PENDING,
            'luggage_refund_amount' => -12000,
        ]);

        ExcessLuggageEscrow::create([
            'booking_id' => $booking->id,
            'booking_code' => $booking->booking_code,
            'held_amount' => 12000,
            'surplus_amount' => 12000,
            'status' => ExcessLuggageEscrow::STATUS_REFUND_PENDING,
        ]);

        $item = $this->service->approveExcessLuggageRefund($booking);

        $this->service->cancelPendingItems(
            DisbursementItem::SOURCE_EXCESS_LUGGAGE,
            $booking->id,
            'Rejected before payout.'
        );
        $this->assertSame(DisbursementItem::STATUS_SKIPPED, $item->fresh()->status);

        // Staff re-submitted the request; approving again must make it payable.
        $reopened = $this->service->approveExcessLuggageRefund($booking->fresh());
        $this->assertSame($item->id, $reopened->id);
        $this->assertSame(DisbursementItem::STATUS_PENDING, $reopened->status);
    }

    /* ---------------------------------------------------------------------
     | Helpers
     * ------------------------------------------------------------------- */

    private function makeBookingRefund(array $attributes): Refund
    {
        return Refund::create($attributes + [
            'fullname' => 'Test Passenger',
            'payout_method' => 'mobile_money',
        ]);
    }

    private function makeLuggageBooking(array $attributes): Booking
    {
        return Booking::create($attributes + [
            'customer_name' => 'Luggage Passenger',
            'customer_phone' => '0712345678',
            'payment_status' => 'Paid',
        ]);
    }

    private function makeHireOrder(array $attributes): SpecialHireOrder
    {
        return SpecialHireOrder::create($attributes + [
            'customer_name' => 'Hire Passenger',
            'customer_phone' => '0712345678',
            'deposit_amount' => 0,
            'balance_amount' => 0,
        ]);
    }

    private function createSchema(): void
    {
        Schema::create('disbursements', function (Blueprint $table) {
            $table->id();
            $table->date('batch_date');
            $table->string('reference')->nullable();
            $table->string('currency')->default('TZS');
            $table->string('status')->default('open');
            $table->unsignedInteger('total_items')->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->unsignedInteger('paid_items')->default(0);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->unsignedInteger('failed_items')->default(0);
            $table->unsignedInteger('needs_details_items')->default(0);
            $table->timestamp('collected_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('disbursement_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('disbursement_id');
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->string('source_reference')->nullable();
            $table->string('booking_code')->nullable();
            $table->string('beneficiary_type')->nullable();
            $table->unsignedBigInteger('beneficiary_user_id')->nullable();
            $table->string('beneficiary_name')->nullable();
            $table->string('payout_channel')->nullable();
            $table->string('payout_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_bic')->nullable();
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('currency')->default('TZS');
            $table->string('status')->default('pending');
            $table->string('reference')->nullable();
            $table->string('gateway_id')->nullable();
            $table->string('gateway_status')->nullable();
            $table->decimal('gateway_fee', 14, 2)->nullable();
            $table->text('gateway_response')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('next_attempt_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();
            $table->unique(['source_type', 'source_id']);
        });

        Schema::create('refund', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code')->nullable();
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->decimal('amount', 14, 2)->nullable();
            $table->string('status')->default('Pending');
            $table->string('phone')->nullable();
            $table->string('fullname')->nullable();
            $table->string('payout_method')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_bic')->nullable();
            $table->timestamp('disbursed_at')->nullable();
            $table->unsignedBigInteger('disbursement_item_id')->nullable();
            $table->timestamps();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code')->nullable();
            $table->unsignedBigInteger('campany_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('payment_status')->nullable();
            $table->string('booking_channel')->nullable();
            $table->decimal('luggage_refund_amount', 14, 2)->nullable();
            $table->string('luggage_payment_status')->nullable();
            $table->timestamps();
        });

        Schema::create('special_hire_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code')->nullable();
            $table->unsignedBigInteger('customer_user_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('deposit_amount', 14, 2)->default(0);
            $table->decimal('balance_amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->timestamp('deposit_paid_at')->nullable();
            $table->timestamp('balance_paid_at')->nullable();
            $table->string('payment_status')->default('pending');
            $table->string('order_status')->default('pending');
            $table->timestamps();
        });

        Schema::create('special_hire_withdrawal_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->string('payment_number')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campany_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('vender_id')->nullable();
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->string('payment_number')->nullable();
            $table->string('status')->default('pending');
            $table->string('reference_number')->nullable();
            $table->timestamps();
        });

        Schema::create('excess_luggage_escrow', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->string('booking_code')->nullable();
            $table->decimal('held_amount', 14, 2)->default(0);
            $table->decimal('surplus_amount', 14, 2)->default(0);
            $table->decimal('refund_amount', 14, 2)->nullable();
            $table->string('status')->nullable();
            $table->timestamp('refund_requested_at')->nullable();
            $table->timestamp('refund_approved_at')->nullable();
            $table->timestamps();
        });
    }
}
