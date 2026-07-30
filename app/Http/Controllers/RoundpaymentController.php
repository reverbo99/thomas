<?php

namespace App\Http\Controllers;

use App\Models\AdminWallet;
use App\Models\Bima;
use App\Models\bus;
use App\Models\Booking;
use App\Models\Campany;
use App\Models\PaymentFees;
use App\Models\Setting;
use App\Models\SystemBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\PercentController;
use App\Services\BookingSettlementService;

class RoundpaymentController extends Controller
{
    /**
     * Settle every Unpaid/resaved booking that shares a gateway transaction ref.
     * Round-trip Mixx/DPO/ClickPesa legs share one ref; if the browser session is
     * gone the single-booking fallback must not leave the return leg unsettled
     * (missing commission + government levies).
     *
     * @return array<int, \App\Models\Booking>|array{errorMessage: string, transactionToken?: string}|null
     */
    public function settleAllByTransactionRef($transToken, $verifyResponse = null, $paymentMethod = null)
    {
        if ($transToken === null || $transToken === '') {
            return null;
        }

        $bookings = Booking::where('transaction_ref_id', $transToken)
            ->whereIn('payment_status', ['Unpaid', 'resaved'])
            ->orderBy('id')
            ->get();

        if ($bookings->count() < 2) {
            return null;
        }

        $settled = [];
        foreach ($bookings as $booking) {
            $result = $this->roundtrip($transToken, $transToken, $verifyResponse, $booking->booking_code, $paymentMethod);
            if (is_array($result) && isset($result['errorMessage'])) {
                return $result;
            }
            $settled[] = $result;
        }

        return $settled;
    }

    public function roundtrip($transToken = null, $companyRef = null, $verifyResponse = null, $code = null, $paymentMethod = null)
    {
        $booking = Booking::where('booking_code', $code)->first();

        if (!$booking) {
            Log::error('Booking not found', [
                'booking_code' => $code,
                'company_ref' => $companyRef,
            ]);
            return [
                'errorMessage' => 'Booking not found',
                'transactionToken' => $transToken
            ];
        }

        if (!in_array($booking->payment_status, ['Unpaid', 'resaved'], true)) {
            Log::warning('Booking already processed', [
                'booking_code' => $code,
                'company_ref' => $companyRef,
            ]);
            return $booking;
        }

        DB::beginTransaction();

        try {
            $settlementService = app(BookingSettlementService::class);
            $settled = $settlementService->settlePaidBooking($booking, [
                'trans_status' => 'success',
                'trans_token' => $transToken,
                'payment_method' => $paymentMethod ?? 'dpo',
                // Consume cancel credit once per settlement call; cleared below so a
                // paired round-trip leg does not receive the same cancel twice.
                'cancel_amount' => Session::get('cancel', 0),
            ]);
            $booking = $settled['booking'];
            $bus = $settled['bus'];
            $busOwnerAmount = $settled['bus_owner_amount'];
            $systemBalanceAmount = $settled['system_balance_amount'];
            $paymentFeesAmount = $settled['payment_fees_amount'];
            $bimaAmount = $booking->bima_amount ?? 0;

            DB::commit();
            Session::forget('cancel');

            // --- TRA INTEGRATION ---
            try {
                Log::info('TRA Fiscalization Starting (Round Trip Payment)', [
                    'booking_id' => $booking->id,
                    'booking_code' => $booking->booking_code,
                    'payment_method' => $paymentMethod ?? 'dpo',
                    'amount' => $booking->amount,
                    'transaction_token' => $transToken,
                ]);

                $tra = new \App\Services\TraVfdService();
                $fiscalized = $tra->fiscalize($booking->refresh());

                if ($fiscalized) {
                    Log::info('TRA Fiscalization Successful (Round Trip Payment)', [
                        'booking_id' => $booking->id,
                        'booking_code' => $booking->booking_code,
                        'tra_status' => $booking->tra_status,
                        'tra_vnum' => $booking->tra_vnum ?? 'N/A',
                    ]);
                } else {
                    Log::warning('TRA Fiscalization Returned False (Round Trip Payment)', [
                        'booking_id' => $booking->id,
                        'booking_code' => $booking->booking_code,
                        'tra_status' => $booking->tra_status ?? 'N/A',
                        'tra_error' => $booking->tra_error ?? 'N/A',
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("TRA Fiscalization Failed (Round Trip Payment): " . $e->getMessage(), [
                    'booking_id' => $booking->id,
                    'booking_code' => $booking->booking_code,
                    'transaction_token' => $transToken,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
            // -----------------------

            Log::info('Round Trip Payment processed successfully', [
                'booking_id' => $booking->id,
                'company_id' => $bus->campany->id,
                'company_balance_increment' => $busOwnerAmount,
                'system_balance' => $systemBalanceAmount,
                'payment_fees' => $paymentFeesAmount,
                'vendor_fee_share' => $booking->vender_fee ?? 0,
                'vendor_service_share' => $booking->vender_service ?? 0,
                'bima_amount' => $bimaAmount,
            ]);

            Session::forget('booking');
            Session::forget('cancel');
            $key = new FunctionsController();
            $key->delete_key($booking);

            return $booking;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process Round Trip payment', [
                'error' => $e->getMessage(),
                'booking_id' => $booking->id,
                'transaction_token' => $transToken
            ]);

            $go = new RoundTripController();
            return $go->paymentFailed($e->getMessage());
        }
    }
}
