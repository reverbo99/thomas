<?php

namespace App\Services;

use App\Models\AdminWallet;
use App\Models\Bima;
use App\Models\Booking;
use App\Models\Bus;
use App\Models\GovernmentLevy;
use App\Models\PaymentFees;
use App\Models\Setting;
use App\Models\SystemBalance;
use App\Models\VenderBalance;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class BookingSettlementService
{
    public function __construct(private readonly FareFormulaService $formulaService)
    {
    }

    public function settlePaidBooking(Booking $booking, array $meta = []): array
    {
        // ── Diagnostic: confirm this version of the code is running ──────────
        Log::warning('Settlement: settlePaidBooking START', [
            'booking_id'     => $booking->id,
            'booking_code'   => $booking->booking_code,
            'vender_id_raw'  => $booking->vender_id,
            'vender_id_gt0'  => ($booking->vender_id > 0),
            'payment_method' => $meta['payment_method'] ?? 'unknown',
            'amount'         => $booking->amount,
            'busFee'         => $booking->busFee,
        ]);

        $adminWallet = AdminWallet::find(1);
        if (!$adminWallet) {
            $adminWallet = AdminWallet::query()->first();
        }
        if (!$adminWallet) {
            $adminWallet = AdminWallet::create([
                'service_balance' => 0,
                'commision_balance' => 0,
                'balance' => 0,
                'vat' => 0,
            ]);
        }

        $bus = Bus::with(['busname', 'route', 'campany.balance'])->find($booking->bus_id);
        if (!$bus || !$bus->campany) {
            throw new \RuntimeException('Bus/company data not found for settlement');
        }

        if (!$bus->campany->balance) {
            $bus->campany->balance()->create([
                'campany_id' => $bus->campany->id,
                'amount' => 0,
                'fees' => 0,
            ]);
            $bus->campany->load('balance');
        }

        $setting = Setting::first();
        $bimaAmount = (float) ($booking->bima_amount ?? 0);
        $totalFare = (float) $booking->amount;
        $busFare = (float) $booking->busFee;
        $cancelAmount = (float) ($meta['cancel_amount'] ?? (session('cancel') ?? 0));
        $paymentMethod = (string) ($meta['payment_method'] ?? '');
        $consumeCancelWallet = !($meta['skip_cancel_wallet_consumption'] ?? false) && $paymentMethod !== 'wallet';

        if ($consumeCancelWallet && auth()->check() && auth()->user()->role === 'customer' && auth()->user()->temp_wallets) {
            $cancelAmount += (float) auth()->user()->temp_wallets->amount;
            auth()->user()->temp_wallets->amount = 0;
            auth()->user()->temp_wallets->save();
        }

        $booking->loadMissing(['vender.VenderAccount', 'vender.VenderBalances']);

        // ── Vendor resolution ────────────────────────────────────────────────
        // $vendorPct = null  → no vendor on this booking → formula gives 0 %.
        // $vendorPct = 0.0   → vendor exists but no percentage row → formula
        //                       applies the 10 % default via fallbackPositive.
        $vendor = $booking->vender_id > 0 ? $booking->vender : null;

        if ($booking->vender_id > 0 && $vendor === null) {
            Log::warning('Settlement: vender_id set but User record not found — commission skipped', [
                'booking_id'  => $booking->id,
                'vender_id'   => $booking->vender_id,
                'reason'      => 'User row missing or soft-deleted',
            ]);
        }

        // When a vendor is present, start at 0.0 so the formula falls back to
        // the 10 % default if no VenderAccount row exists.  Passing null would
        // tell the formula "no vendor" and produce 0 % commission.
        $vendorPct = $vendor ? 0.0 : null;

        if ($vendor) {
            if ($vendor->VenderAccount) {
                $vendorPct = (float) ($vendor->VenderAccount->percentage ?? 0);
                Log::info('Settlement: vendor percentage from VenderAccount', [
                    'booking_id' => $booking->id,
                    'vender_id'  => $vendor->id,
                    'percentage' => $vendorPct,
                ]);
            } else {
                Log::warning('Settlement: vendor has no VenderAccount row — using default 10 % commission', [
                    'booking_id' => $booking->id,
                    'vender_id'  => $vendor->id,
                    'reason'     => 'No vender_account row for this user; fallbackPositive will apply 10 %',
                ]);
            }
        } else {
            Log::info('Settlement: no vendor on this booking — vendor commission is 0', [
                'booking_id' => $booking->id,
                'vender_id'  => $booking->vender_id,
            ]);
        }

        $result = $this->formulaService->calculateSettlement(
            $totalFare,
            $busFare,
            $bimaAmount,
            $cancelAmount,
            $setting,
            $bus->campany,
            $vendorPct,
            $this->countBookingSeats($booking->seat)
        );

        Log::info('Settlement: formula result', [
            'booking_id'             => $booking->id,
            'total_fare'             => $totalFare,
            'bus_fare'               => $busFare,
            'vendor_percent_used'    => $result['rates']['vendor_percent'],
            'commission_to_vendor'   => $result['commission_to_vendor'],
            'service_fees_to_vendor' => $result['service_fees_to_vendor'],
            'system_commission'      => $result['system_commission_total'],
            'bus_owner_share'        => $result['bus_owner_share'],
        ]);

        $systemBalanceAmount = (float) $result['system_commission_total'];
        // Excess luggage is paid with the booking total but is not platform service income.
        // Subtract it so payment_fees / service levy stay service-only; luggage is tracked on bookings.
        $luggageFee = booking_luggage_fee($booking);
        $paymentFeesAmount = max(0, (float) $result['service_pool_after_vendor'] - $luggageFee);
        $vendorFee = 0.0;
        $vendorService = 0.0;

        if ($vendor) {
            $vendorFee    = (float) $result['commission_to_vendor'];
            $vendorService = (float) $result['service_fees_to_vendor'];

            if (($vendorFee + $vendorService) <= 0) {
                Log::warning('Settlement: vendor commission resolved to zero — check formula rates', [
                    'booking_id'           => $booking->id,
                    'vender_id'            => $vendor->id,
                    'vendor_percent_used'  => $result['rates']['vendor_percent'],
                    'system_commission'    => $result['system_commission_total'],
                    'reason'               => 'commission_to_vendor + service_fees_to_vendor = 0; verify FareFormulaService rates',
                ]);
            }

            $vendorBalance = $vendor->VenderBalances ?: VenderBalance::firstOrCreate(
                ['user_id' => $vendor->id],
                ['amount'  => 0]
            );

            if ($vendorBalance->wasRecentlyCreated) {
                Log::info('Settlement: created missing vender_balances row', [
                    'booking_id' => $booking->id,
                    'vender_id'  => $vendor->id,
                ]);
            }

            if ($vendorBalance->amount === null) {
                Log::warning('Settlement: vender_balances.amount was NULL — resetting to 0 before increment', [
                    'booking_id' => $booking->id,
                    'vender_id'  => $vendor->id,
                ]);
                $vendorBalance->forceFill(['amount' => 0])->save();
            }

            $vendorBalance->increment('amount', $vendorFee + $vendorService);

            Log::info('Settlement: vendor commission credited', [
                'booking_id'     => $booking->id,
                'vender_id'      => $vendor->id,
                'vendor_fee'     => $vendorFee,
                'vendor_service' => $vendorService,
                'total_credited' => $vendorFee + $vendorService,
                'new_balance'    => $vendorBalance->fresh()->amount,
            ]);

            $systemBalanceAmount = max(0, $systemBalanceAmount - $vendorFee);
        }

        if ($bimaAmount > 0) {
            Bima::create([
                'booking_id' => $booking->id,
                'start_date' => $booking->travel_date,
                'end_date' => $booking->insuranceDate,
                'amount' => $bimaAmount,
                'bima_vat' => $bimaAmount * (18 / 118),
            ]);
            $adminWallet->increment('balance', $bimaAmount);
        }

        $bookingUpdatePayload = array_merge([
            'payment_status' => 'Paid',
            'trans_status' => $meta['trans_status'] ?? 'success',
            'fee' => $systemBalanceAmount,
            'service' => $paymentFeesAmount,
            'vender_fee' => $vendorFee,
            'vender_service' => $vendorService,
            'government_levy' => $result['government_levy_on_fare'],
            'system_service_fee' => $result['service_fees'],
            // Preserve gateway/checkout total; `amount` becomes bus-owner share after settlement.
            'customer_paid_total' => $totalFare,
            'amount' => $result['bus_owner_share'],
            'payment_method' => $meta['payment_method'] ?? null,
        ], $this->transactionMeta($meta));
        $booking->update($this->filterExistingBookingColumns($bookingUpdatePayload, $booking->id));

        SystemBalance::create([
            'campany_id' => $bus->campany->id,
            'booking_id' => $booking->booking_code,
            'balance' => $systemBalanceAmount,
        ]);
        
        // Calculate government levy on service fees (5%)
        $governmentLevyOnServiceFee = $paymentFeesAmount * 0.05;
        $serviceFeeAfterLevy = $paymentFeesAmount - $governmentLevyOnServiceFee;
        
        PaymentFees::create([
            'campany_id' => $bus->campany->id,
            'amount' => $serviceFeeAfterLevy,
            'booking_id' => $booking->booking_code,
        ]);
        
        GovernmentLevy::create([
            'campany_id' => $bus->campany->id,
            'booking_id' => $booking->booking_code,
            'amount' => $governmentLevyOnServiceFee,
        ]);

        $adminWallet->increment('balance', $systemBalanceAmount + $serviceFeeAfterLevy + $luggageFee);
        $bus->campany->balance->increment('amount', (float) $result['bus_owner_share']);

        return [
            'booking' => $booking->fresh(),
            'bus' => $bus,
            'bus_owner_amount' => (float) $result['bus_owner_share'],
            'system_balance_amount' => $systemBalanceAmount,
            'payment_fees_amount' => $paymentFeesAmount,
            'luggage_fee' => $luggageFee,
            'vendor_fee_share' => $vendorFee,
            'vendor_service_share' => $vendorService,
            'government_levy' => (float) $result['government_levy_on_fare'],
        ];
    }

    private function countBookingSeats(?string $seat): int
    {
        if ($seat === null || trim($seat) === '') {
            return 1;
        }

        $seats = array_filter(array_map('trim', explode(',', $seat)));

        return max(1, count($seats));
    }

    private function transactionMeta(array $meta): array
    {
        $allowed = ['mfs_id', 'verification_code', 'trans_token'];
        $output = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $meta)) {
                $output[$key] = $meta[$key];
            }
        }
        return $output;
    }

    /**
     * Keep payment settlement resilient when some environments are behind on migrations.
     * Unknown columns are skipped and logged instead of breaking successful payments.
     */
    private function filterExistingBookingColumns(array $payload, int $bookingId): array
    {
        static $bookingColumns = null;

        if ($bookingColumns === null) {
            try {
                $bookingColumns = array_flip(Schema::getColumnListing('bookings'));
            } catch (\Throwable $e) {
                Log::warning('Could not read bookings schema; applying unfiltered payload', [
                    'booking_id' => $bookingId,
                    'error' => $e->getMessage(),
                ]);
                return $payload;
            }
        }

        $filtered = array_intersect_key($payload, $bookingColumns);
        $missingColumns = array_diff(array_keys($payload), array_keys($filtered));

        if (!empty($missingColumns)) {
            Log::warning('Skipping missing booking columns during settlement', [
                'booking_id' => $bookingId,
                'missing_columns' => array_values($missingColumns),
            ]);
        }

        return $filtered;
    }
}

