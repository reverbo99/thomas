<?php

namespace App\Services;

use App\Models\Setting;

/**
 * Bus fare, service fee, and settlement math aligned with "New Formular Update(1).xlsx".
 *
 * Multi-seat: pass the combined levy-inclusive bus fare (seats × per-seat price). Do not multiply
 * rates by seat count again — the same rule applies at checkout and in settlement.
 *
 * - System commission: (total bus fare levy-inclusive × commission %) + commission adding.
 * - Service fees (admin / settlement): (total bus fare levy-inclusive × service %) + service adding.
 * - Traveller service fee (checkout): same as settlement service fees.
 * - Government levy on service fee: 5% of admin service fees.
 * - Rates may be stored as decimals (0.05 = 5%) or whole percents (5 = 5%).
 */
class FareFormulaService
{
    private const DEFAULT_COMMISSION_PERCENT = 5.0;

    private const DEFAULT_SERVICE_PERCENT = 2.0;

    private const DEFAULT_SERVICE_ADDING = 100.0;

    public const DEFAULT_VENDOR_PERCENT = 10.0;

    private const DEFAULT_GOVERNMENT_LEVY_PERCENT = 5.0;

    private const SAFIRI_DOMESTIC_PER_DAY = 100.0;

    private const SAFIRI_FOREIGN_PER_DAY = 200.0;

    public function resolveRates(?Setting $setting, $company = null, ?float $vendorPercentage = null): array
    {
        $servicePercent = $this->fallbackPositive($setting?->service_percentage, self::DEFAULT_SERVICE_PERCENT);
        $servicePercent = $this->normalizePercentValue((float) $servicePercent);
        $serviceAdding = $this->fallbackPositive($setting?->service, self::DEFAULT_SERVICE_ADDING);

        $commissionPercent = self::DEFAULT_COMMISSION_PERCENT;
        if ($company && (float) ($company->percentage ?? 0) > 0) {
            $commissionPercent = min(100.0, $this->normalizePercentValue((float) $company->percentage));
        }

        // System commission adding figure: admin "Amount" on company row (not a full override of commission).
        $commissionAdding = $company ? (float) ($company->commission_amount ?? 0) : 0.0;

        // Vendor percentage: null means no vendor involved (direct booking), so 0%.
        // The caller (BookingSettlementService) pre-resolves the default fallback
        // so that 0.0 means explicitly 0% — not "unset".
        if ($vendorPercentage === null) {
            $vendorPercent = 0.0;
        } else {
            $vendorPercent = min(100.0, $this->normalizePercentValue((float) $vendorPercentage));
        }

        return [
            'commission_percent' => $commissionPercent,
            'commission_adding' => $commissionAdding,
            'service_percent' => $servicePercent,
            'service_adding' => $serviceAdding,
            'vendor_percent' => $vendorPercent,
            'government_levy_percent' => self::DEFAULT_GOVERNMENT_LEVY_PERCENT,
        ];
    }

    /**
     * Traveller-facing service fee at checkout: (total bus fare × service %) + (service adding × seat count).
     *
     * The flat service_adding is charged once per seat, so two seats at 140 each = 280 total service fee.
     *
     * @param  float  $typeFare   Total bus fare for all selected seat(s) as shown in booking
     * @param  int    $seatCount  Number of seats selected (default 1)
     */
    public function calculateTravellerServiceFee(float $typeFare, ?Setting $setting, int $seatCount = 1): float
    {
        $rates = $this->resolveRates($setting);
        $count = max(1, $seatCount);

        return ($typeFare * ($rates['service_percent'] / 100)) + ($rates['service_adding'] * $count);
    }

    /**
     * Bus fare base for service-fee calculation (uses discounted fare when a coupon was applied).
     *
     * @param  array<string, mixed>  $bookingForm
     */
    public function busFareForServiceFeeFromBookingForm(array $bookingForm): float
    {
        $dispo = (float) ($bookingForm['dispo'] ?? 0);
        if ($dispo > 0) {
            return $dispo;
        }

        return (float) ($bookingForm['total_amount'] ?? 0);
    }

    /**
     * Extract the number of seats from a booking form session array.
     * Handles both comma-separated strings ("A1,A2") and arrays.
     *
     * @param  array<string, mixed>  $bookingForm
     */
    public function seatCountFromBookingForm(array $bookingForm): int
    {
        $seats = $bookingForm['seats'] ?? '';
        if (is_array($seats)) {
            return max(1, count(array_filter($seats)));
        }
        if (is_string($seats) && $seats !== '') {
            $parts = array_filter(array_map('trim', explode(',', $seats)));
            return max(1, count($parts));
        }
        return 1;
    }

    /**
     * Extract seat count from a stored booking seat string (e.g. "A1,A2" → 2).
     */
    public function seatCountFromSeatString(?string $seatString): int
    {
        if (empty($seatString)) {
            return 1;
        }
        $parts = array_filter(array_map('trim', explode(',', $seatString)));
        return max(1, count($parts));
    }

    public function calculateTravellerTotal(array $input, ?Setting $setting): array
    {
        $busFare = (float) ($input['bus_fare'] ?? 0);
        $domesticDays = max(0, (int) ($input['safiri_domestic_days'] ?? 0));
        $foreignDays = max(0, (int) ($input['safiri_foreign_days'] ?? 0));
        $mpesa = max(0, (float) ($input['mpesa_tariff'] ?? 0));
        $mixx = max(0, (float) ($input['mixx_tariff'] ?? 0));
        $airtel = max(0, (float) ($input['airtel_tariff'] ?? 0));

        $serviceFee = $this->calculateTravellerServiceFee($busFare, $setting);
        $domestic = self::SAFIRI_DOMESTIC_PER_DAY * $domesticDays;
        $foreign = self::SAFIRI_FOREIGN_PER_DAY * $foreignDays;

        return [
            'bus_fare' => $busFare,
            'service_fee' => $serviceFee,
            'safiri_domestic' => $domestic,
            'safiri_foreign' => $foreign,
            'mpesa_tariff' => $mpesa,
            'mixx_tariff' => $mixx,
            'airtel_tariff' => $airtel,
            'total_to_pay' => $busFare + $serviceFee + $domestic + $foreign + $mpesa + $mixx + $airtel,
        ];
    }

    public function calculateSettlement(
        float $totalFareLevyInclusive,
        float $busFareLevyInclusive,
        float $bimaAmount,
        float $cancelAmount,
        ?Setting $setting,
        $company = null,
        ?float $vendorPercentage = null,
        ?int $seatCount = null
    ): array {
        $rates = $this->resolveRates($setting, $company, $vendorPercentage);
        $governmentLevyOnFare = $busFareLevyInclusive * ($rates['government_levy_percent'] / 100);
        $levyExclusiveFare = $this->levyExclusiveFromInclusive(
            $busFareLevyInclusive,
            $rates['government_levy_percent']
        );

        // Same as traveller checkout: total levy-inclusive bus fare for all seats (no extra × seat count).
        $systemCommissionTotal = ($busFareLevyInclusive * ($rates['commission_percent'] / 100))
            + $rates['commission_adding'];

        $serviceFees = $this->calculateTravellerServiceFee($busFareLevyInclusive, $setting, max(1, (int) $seatCount));

        $commissionToVendor = $systemCommissionTotal * ($rates['vendor_percent'] / 100);
        $serviceFeesToVendor = $serviceFees * ($rates['vendor_percent'] / 100);
        $systemCommissionRemainder = $systemCommissionTotal - $commissionToVendor;

        $busOwnerShare = $busFareLevyInclusive - $systemCommissionTotal - $governmentLevyOnFare + $cancelAmount;
        $busFareRemainder = $busFareLevyInclusive - ($systemCommissionTotal + $governmentLevyOnFare);
        $amountOnBusOwnerFormula = $rates['commission_adding'];

        $servicePoolAfterVendor = max(0, $serviceFees - $serviceFeesToVendor);
        $governmentLevyOnServiceFee = $servicePoolAfterVendor * ($rates['government_levy_percent'] / 100);
        $totalGovernmentLevies = $governmentLevyOnFare + $governmentLevyOnServiceFee;

        return [
            'rates' => $rates,
            'total_fare_levy_inclusive' => $totalFareLevyInclusive,
            'total_fare_levy_exclusive' => $levyExclusiveFare,
            'government_levy_on_fare' => $governmentLevyOnFare,
            'service_fees' => $serviceFees,
            'government_levy_on_service_fee' => $governmentLevyOnServiceFee,
            'total_government_levies' => $totalGovernmentLevies,
            'system_commission_total' => $systemCommissionTotal,
            'commission_to_vendor' => $commissionToVendor,
            'service_fees_to_vendor' => $serviceFeesToVendor,
            'system_commission_remainder' => $systemCommissionRemainder,
            'bus_fare_remainder' => $busFareRemainder,
            'amount_on_bus_owner_formula' => $amountOnBusOwnerFormula,
            'bus_owner_share' => $busOwnerShare,
            'service_pool_after_vendor' => $servicePoolAfterVendor,
            'highlink_share_user_ticket' => $systemCommissionTotal + $serviceFees,
            'highlink_share_vendor_ticket' => $systemCommissionRemainder + $servicePoolAfterVendor,
            'bima_amount' => $bimaAmount,
        ];
    }

    /**
     * Levy-exclusive fare (cell B14): levy-inclusive total minus government levy on fare (B21).
     */
    private function levyExclusiveFromInclusive(float $levyInclusive, float $governmentLevyPercent): float
    {
        $governmentLevyOnFare = $levyInclusive * ($governmentLevyPercent / 100);

        return $levyInclusive - $governmentLevyOnFare;
    }

    private function fallbackPositive($value, float $fallback): float
    {
        $number = (float) $value;
        return $number > 0 ? $number : $fallback;
    }

    /** Values in (0, 1) are treated as fractions (0.05 → 5%); 1.0+ kept as-is. */
    private function normalizePercentValue(float $value): float
    {
        if ($value <= 0) {
            return 0.0;
        }
        return $value > 0 && $value < 1 ? $value * 100.0 : $value;
    }
}
