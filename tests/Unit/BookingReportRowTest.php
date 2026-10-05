<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class BookingReportRowTest extends TestCase
{
    private function vendorBooking(): object
    {
        return (object) [
            'booking_code' => 'TM60496503',
            'campany_id' => 3,
            'bus_id' => 6,
            'fee' => 1800,
            'vender_fee' => 200,
            'vender_service' => 90,
            'service' => 810,
            'system_service_fee' => 900,
            'service_vat' => 0,
            'busFee' => 40000,
            'amount' => 36000,
            'government_levy' => 2000,
            'discount_amount' => 0,
            'vat' => 0,
            'bima_amount' => 0,
            'customer_paid_total' => 36000,
            'has_excess_luggage' => 0,
            'excess_luggage_fee' => 0,
            'excess_luggage_description' => null,
            'seat' => 'A2',
            'pickup_point' => 'Kia',
            'dropping_point' => 'Mbezi magufuli',
            'customer_name' => 'Amina Juma',
            'customer_phone' => '255712334455',
            'gender' => 'Female',
            'age' => 28,
            'age_group' => 'Adult',
            'infant_child' => 0,
            'vender_id' => 36,
            'booking_channel' => 'in_person',
            'travel_date' => '2026-09-22',
            'created_at' => null,
            'campany' => (object) ['name' => 'Thomas Express'],
            'schedule' => (object) ['from' => 'Arusha', 'to' => 'Dar es salaam', 'start' => '10:00:00', 'end' => '07:30:00'],
            'bus' => (object) ['bus_number' => 'T 344 DFM', 'route' => (object) ['from' => 'Arusha', 'to' => 'Dar es salaam'], 'campany' => (object) ['name' => 'Thomas Express']],
            'vender' => (object) ['name' => 'Thomas Paul Chizi'],
        ];
    }

    public function test_gross_commission_is_fee_plus_vendor_fee_only(): void
    {
        $booking = $this->vendorBooking();

        $this->assertSame(2000.0, booking_gross_commission($booking));
    }

    public function test_income_report_commission_excludes_vendor_service(): void
    {
        $row = booking_to_report_row($this->vendorBooking());

        $this->assertSame('2000', $row['commision']);
        $this->assertNotSame('2090', $row['commision']);
        $this->assertSame('90', $row['vendor_service']);
        $this->assertSame('900', $row['service_fee']);
    }

    public function test_vendor_booking_report_hides_platform_fee_lines(): void
    {
        $this->assertTrue(booking_report_hides_platform_fees('vendor'));
        $this->assertFalse(booking_report_hides_platform_fees('admin'));
        $this->assertFalse(booking_report_hides_platform_fees(null));
        $this->assertFalse(booking_report_shows_service_fee('bus_owner'));
        $this->assertFalse(booking_report_shows_insurance('bus_owner'));
        $this->assertFalse(booking_report_shows_vendor_share('bus_owner'));
        $this->assertTrue(booking_report_shows_service_fee(null));
    }

    public function test_fare_commission_is_five_percent_of_bus_fare(): void
    {
        $booking = $this->vendorBooking();

        $this->assertSame(2000.0, booking_fare_commission($booking));
        $this->assertSame('2000', booking_to_report_row($booking)['commision']);
    }

    public function test_weighed_luggage_fee_uses_updated_booking_charge(): void
    {
        $booking = $this->vendorBooking();
        $booking->has_excess_luggage = 1;
        $booking->excess_luggage_fee = 15000;
        $booking->luggage_weighed_at = '2026-09-22 10:00:00';
        $booking->luggage_weight_verdict = 'overestimated';
        $booking->actual_weight = 6;
        $booking->excessLuggageEscrow = (object) [
            'actual_fee' => 15000,
            'held_amount' => 20000,
            'admin_share' => 750,
            'status' => 'released',
        ];

        $this->assertSame(15000.0, booking_luggage_fee($booking));
        $this->assertSame(750.0, system_luggage_fee($booking));
        $this->assertSame(750.0, government_luggage_fee($booking));
    }

    public function test_reconciled_customer_total_uses_actual_luggage_not_estimated(): void
    {
        $booking = $this->vendorBooking();
        // customer_paid_total is the checkout snapshot and still holds the declared deposit.
        $booking->customer_paid_total = 60000;
        $booking->has_excess_luggage = 1;
        $booking->estimated_weight = 10;
        $booking->excess_luggage_fee = 15000;
        $booking->actual_weight = 6;
        $booking->luggage_weighed_at = '2026-09-22 10:00:00';
        $booking->luggage_weight_verdict = 'overestimated';
        $booking->excessLuggageEscrow = (object) [
            'actual_fee' => 15000,
            'released_fee' => 15000,
            'held_amount' => 25000,
            'admin_share' => 750,
            'owner_share' => 13500,
            'status' => 'released',
        ];

        // fare 40,000 + actual luggage 15,000 + service 900 + insurance 0
        $this->assertSame(55900.0, booking_reconciled_customer_total($booking));
        // Revenue cards must agree with the history total.
        $this->assertSame(55900.0, booking_reported_revenue($booking));
        // Must never fall back to the stale estimated checkout snapshot.
        $this->assertNotSame(60000.0, booking_reconciled_customer_total($booking));
    }
}
