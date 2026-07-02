<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Setting;
use App\Services\BookingSettlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * Test Payment Controller
 *
 * Simulates payment without real gateways when admin enables test mode.
 * Uses the same settlement pipeline as production ({@see BookingSettlementService}),
 * which applies {@see \App\Services\FareFormulaService} (Thomas sheet logic).
 */
class TestPaymentController extends Controller
{
    /**
     * Process a test payment (simulates successful payment without real gateway)
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function processPayment(Request $request)
    {
        // Verify test mode is enabled
        $settings = Setting::first();
        if (!($settings->test_mode ?? false)) {
            return redirect()->route('home')->with('error', 'Test mode is not enabled. Please use normal payment methods.');
        }

        // Get booking from session
        $booking = session('booking');
        $booking1 = session('booking1');
        $booking2 = session('booking2');

        // Handle round-trip bookings
        if (!is_null($booking1) && !is_null($booking2)) {
            return $this->processRoundTripTestPayment($booking1, $booking2);
        }

        // Handle single booking
        if (!$booking || !isset($booking->booking_code)) {
            Log::error('TestPayment: No booking found in session');
            return redirect()->route('home')->with('error', 'No booking found. Please start again.');
        }

        // Refresh booking from database to get latest data
        $booking = Booking::where('booking_code', $booking->booking_code)->first();

        if (!$booking) {
            Log::error('TestPayment: Booking not found in database', ['booking_code' => session('booking')->booking_code ?? 'N/A']);
            return redirect()->route('home')->with('error', 'Booking not found. Please start again.');
        }

        // Check if already paid
        if ($booking->payment_status === 'Paid') {
            Log::info('TestPayment: Booking already paid', ['booking_code' => $booking->booking_code]);
            return $this->redirectAfterPayment($booking);
        }

        // Generate a test transaction reference
        $transToken = 'TEST-' . strtoupper(uniqid() . rand(1000, 9999));

        // Begin transaction
        DB::beginTransaction();

        try {
            $settlementService = app(BookingSettlementService::class);
            $settled = $settlementService->settlePaidBooking($booking, [
                'trans_status' => 'success',
                'trans_token' => $transToken,
                'payment_method' => 'test_mode',
                'cancel_amount' => Session::get('cancel', 0),
            ]);

            $booking = $settled['booking'];

            DB::commit();

            try {
                $tra = new \App\Services\TraVfdService();
                $tra->fiscalize($booking->refresh());
            } catch (\Exception $e) {
                Log::warning('TestPayment: TRA fiscalization failed', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
            }

            Log::info('TestPayment: Payment processed successfully', [
                'booking_id' => $booking->id,
                'booking_code' => $booking->booking_code,
                'trans_token' => $transToken,
                'amount' => $booking->amount,
            ]);

            // Clear session data
            Session::forget('booking');
            Session::forget('cancel');

            // Delete any temporary keys
            $keyController = new FunctionsController();
            $keyController->delete_key($booking);

            // Redirect to success page
            $redirectController = new RedirectController();
            return $redirectController->_redirect($booking->id);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('TestPayment: Failed to process payment', [
                'error' => $e->getMessage(),
                'booking_id' => $booking->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);

            return view('clickpesa.error', [
                'message' => 'Test payment failed: ' . $e->getMessage(),
                'reference' => $transToken ?? 'N/A',
            ]);
        }
    }

    /**
     * Process round-trip test payment
     *
     * @param Booking $booking1
     * @param Booking $booking2
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    private function processRoundTripTestPayment($booking1, $booking2)
    {
        $code1 = $booking1->booking_code ?? 'N/A';
        $code2 = $booking2->booking_code ?? 'N/A';

        // Generate test transaction references
        $transToken1 = 'TEST-RT1-' . strtoupper(uniqid() . rand(1000, 9999));
        $transToken2 = 'TEST-RT2-' . strtoupper(uniqid() . rand(1000, 9999));

        try {
            $roundController = new RoundpaymentController();

            // Create mock verify responses for test mode
            $verifyResponse1 = (object) [
                'status' => 'success',
                'reference' => $transToken1,
                'amount' => $booking1->amount,
                'message' => 'Test payment successful',
            ];

            $verifyResponse2 = (object) [
                'status' => 'success',
                'reference' => $transToken2,
                'amount' => $booking2->amount,
                'message' => 'Test payment successful',
            ];

            $data1 = $roundController->roundtrip($transToken1, $transToken1, $verifyResponse1, $code1, 'test_mode');
            $data2 = $roundController->roundtrip($transToken2, $transToken2, $verifyResponse2, $code2, 'test_mode');

            if (is_array($data1) && isset($data1['errorMessage'])) {
                session()->forget(['booking1', 'booking2', 'is_round', 'booking_form']);
                return view('clickpesa.error', [
                    'message' => $data1['errorMessage'] ?? 'Booking not found',
                    'reference' => $transToken1,
                ]);
            }

            if (is_array($data2) && isset($data2['errorMessage'])) {
                session()->forget(['booking1', 'booking2', 'is_round', 'booking_form']);
                return view('clickpesa.error', [
                    'message' => $data2['errorMessage'] ?? 'Booking not found',
                    'reference' => $transToken2,
                ]);
            }

            // Clear round trip session data after successful processing
            session()->forget(['booking1', 'booking2', 'is_round', 'booking_form']);

            Log::info('TestPayment: Round-trip payment processed successfully', [
                'booking1_code' => $code1,
                'booking2_code' => $code2,
                'trans_token1' => $transToken1,
                'trans_token2' => $transToken2,
            ]);

            $redirectController = new RedirectController();
            return $redirectController->showRoundTripBookingStatus($data1, $data2);

        } catch (\Exception $e) {
            Log::error('TestPayment: Round-trip payment processing failed', [
                'error' => $e->getMessage(),
                'booking1_code' => $code1,
                'booking2_code' => $code2,
                'trace' => $e->getTraceAsString(),
            ]);

            session()->forget(['booking1', 'booking2', 'is_round', 'booking_form']);

            return view('clickpesa.error', [
                'message' => 'Round-trip test payment failed: ' . $e->getMessage(),
                'reference' => $transToken1 ?? 'N/A',
            ]);
        }
    }

    /**
     * Redirect after successful payment based on user role
     *
     * @param Booking $booking
     * @return \Illuminate\Http\RedirectResponse
     */
    private function redirectAfterPayment(Booking $booking)
    {
        $message = 'Payment successful (Test Mode)';

        if (auth()->check() && auth()->user()->role === 'customer') {
            return redirect()->route('customer.mybooking')->with('success', $message);
        }

        return redirect()->route('booking.status', $booking->id)->with('success', $message);
    }

    /**
     * Get test mode status for frontend checks
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function status()
    {
        $settings = Setting::first();
        return response()->json([
            'test_mode' => (bool) ($settings->test_mode ?? false),
        ]);
    }
}
