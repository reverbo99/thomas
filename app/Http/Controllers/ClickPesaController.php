<?php

namespace App\Http\Controllers;

use App\Models\AdminWallet;
use App\Models\Bima;
use App\Models\Booking;
use App\Models\bus;
use App\Models\Campany;
use App\Models\PaymentFees;
use App\Models\Roundtrip;
use App\Models\SpecialHireOrder;
use App\Services\SpecialHireOrderPaymentService;
use App\Models\Setting;
use App\Models\SystemBalance;
use App\Services\BookingSettlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\FunctionsController;
use App\Http\Controllers\PercentController;
use App\Http\Controllers\RedirectController;
use App\Http\Controllers\VenderWalletController;

class ClickPesaController extends Controller
{
    // ClickPesa API Configuration
    private $apiKey;
    private $clientId;
    /** @var string Checksum key/secret for HMAC (from ClickPesa dashboard) */
    private $checksumKey;
    private $endpoint;

    public function __construct()
    {
        $this->apiKey = env('CLICKPESA_API_KEY'); // Your ClickPesa API Key
        $this->clientId = env('CLICKPESA_CLIENT_ID'); // Your ClickPesa Client ID
        $this->checksumKey = env('CLICKPESA_CHECKSUM_KEY', $this->clientId ?? ''); // Checksum key from ClickPesa (for HMAC)
        $this->endpoint = env('CLICKPESA_ENDPOINT', 'https://api.clickpesa.com/third-parties/payments/preview-ussd-push-request');
    }

    /**
     * Route user back to the correct payment screen for their portal.
     */
    private function paymentRouteForContext(): string
    {
        if (auth()->check()) {
            if (auth()->user()->role === 'customer') {
                return 'customer.pay';
            }
            if (auth()->user()->role === 'vender') {
                return 'vender.pay';
            }
        }

        return 'pay';
    }

    /**
     * Normalize a MSISDN for ClickPesa (Tanzania mobile money: 255 + 9 digits, typically 06/07 national).
     *
     * @return array{ok: bool, phone: string, error: string|null}
     */
    public static function normalizeTanzaniaMsisdnForClickPesa(string $raw): array
    {
        $phoneNumber = preg_replace('/\D+/', '', $raw) ?? '';
        if ($phoneNumber === '') {
            return [
                'ok' => false,
                'phone' => '',
                'error' => 'Enter a mobile money number (e.g. 07xxxxxxxx or 2557xxxxxxxx). Country code 255 is required for ClickPesa.',
            ];
        }
        if (str_starts_with($phoneNumber, '00')) {
            $phoneNumber = substr($phoneNumber, 2);
        }
        if (str_starts_with($phoneNumber, '255')) {
            while (strlen($phoneNumber) > 12 && isset($phoneNumber[3]) && $phoneNumber[3] === '0') {
                $phoneNumber = substr($phoneNumber, 0, 3) . substr($phoneNumber, 4);
            }
        } elseif (str_starts_with($phoneNumber, '0')) {
            $phoneNumber = '255' . substr($phoneNumber, 1);
        } else {
            $phoneNumber = '255' . $phoneNumber;
        }
        while (strlen($phoneNumber) > 12 && isset($phoneNumber[3]) && $phoneNumber[3] === '0') {
            $phoneNumber = substr($phoneNumber, 0, 3) . substr($phoneNumber, 4);
        }
        if (!preg_match('/^255[67]\d{8}$/', $phoneNumber)) {
            return [
                'ok' => false,
                'phone' => $phoneNumber,
                'error' => 'Use a valid Tanzania mobile money number with country code 255 (e.g. 2557xxxxxxxx or 07xxxxxxxx). ClickPesa rejects numbers that are not 255 followed by nine digits.',
            ];
        }

        return ['ok' => true, 'phone' => $phoneNumber, 'error' => null];
    }

    /**
     * Whether a ClickPesa payment row status means funds were successfully collected.
     * Kept in sync with {@see ClickPesaController::checkPaymentStatus()} so web, callback, and API verify agree.
     */
    public static function clickPesaPaidStatus(?string $status): bool
    {
        $s = strtoupper(trim((string) ($status ?? '')));

        return in_array($s, ['SUCCESS', 'SUCCESSFUL', 'COMPLETED', 'PAID', 'SETTLED'], true);
    }

    /**
     * Initiate ClickPesa payment
     */
    public function initiatePayment($amount, $first_name, $last_name, $phone, $email, $order_id = null)
    {
        // Prepare order details
        $orderDetails = [
            'amount' => $amount,
            'order_id' => $order_id ?? 'ORD-' . now()->timestamp,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'phone' => $phone,
            'email' => $email,
            'redirect_url' => route('clickpesa.callback'),
            'cancel_url' => route('clickpesa.cancel'),
        ];

        // Create transaction with ClickPesa
        $checkoutResponse = $this->createCheckoutSession($orderDetails);

        // Check if response is a string (error) or object (success)
        if (is_string($checkoutResponse)) {
            // Handle error case (truncate long API/HTML responses for session flash)
            $orderReference = preg_replace('/[^a-zA-Z0-9]/', '', $orderDetails['order_id']);
            $errorMsg = strlen($checkoutResponse) > 300 ? substr($checkoutResponse, 0, 300) . '…' : $checkoutResponse;
            Log::error('ClickPesa Checkout Creation Failed', [
                'order_id' => $orderDetails['order_id'],
                'order_reference' => $orderReference,
                'error' => $checkoutResponse,
            ]);

            return redirect()->route($this->paymentRouteForContext())
                ->with('error', 'ClickPesa Payment Failed: ' . $errorMsg);
        }

        // Check if we have a valid response with checkout URL
        // ClickPesa USSD-PUSH doesn't return a URL, it sends payment request to phone
        // Response includes: id, status, channel, orderReference, etc.
        $checkoutUrl = null;
        
        // For USSD-PUSH, we check if the request was successfully initiated
        // IMPORTANT: Only proceed if status indicates success (not failed/error)
        if ($checkoutResponse && isset($checkoutResponse->id) && isset($checkoutResponse->status)) {
            $transactionId = (string) $checkoutResponse->id;
            $status = strtolower((string) $checkoutResponse->status);
            
            // Check if status indicates failure - don't proceed if failed
            if (in_array($status, ['failed', 'error', 'rejected', 'cancelled', 'invalid'])) {
                $errorMessage = isset($checkoutResponse->message) 
                    ? (string) $checkoutResponse->message 
                    : "Payment request failed with status: " . $status;
                Log::error('ClickPesa USSD-PUSH Request Failed - Invalid Status', [
                    'order_id' => $orderDetails['order_id'],
                    'transaction_id' => $transactionId,
                    'status' => $status,
                    'error_message' => $errorMessage,
                    'response' => $checkoutResponse
                ]);
                return redirect()->route($this->paymentRouteForContext())
                    ->with('error', 'ClickPesa Payment Failed: ' . $errorMessage);
            }
            
            // Use same alphanumeric format we send to ClickPesa so polling and callback work
            $orderRef = isset($checkoutResponse->orderReference)
                ? (string) $checkoutResponse->orderReference
                : preg_replace('/[^a-zA-Z0-9]/', '', $orderDetails['order_id']);

            // Log successful USSD-PUSH initiation
            Log::info('ClickPesa USSD-PUSH Initiated Successfully', [
                'order_id' => $orderDetails['order_id'],
                'transaction_id' => $transactionId,
                'order_reference' => $orderRef,
                'status' => $status,
                'amount' => $orderDetails['amount']
            ]);

            // CRITICAL: Store the order reference in the booking immediately
            // This ensures we can find the booking later even if session is lost
            try {
                $sessionBooking = session('booking');
                if ($sessionBooking && isset($sessionBooking->booking_code)) {
                    Booking::where('booking_code', $sessionBooking->booking_code)
                        ->update([
                            'transaction_ref_id' => $orderRef,
                            'external_ref_id' => $transactionId
                        ]);
                    Log::info('ClickPesa: Stored order reference in booking', [
                        'booking_code' => $sessionBooking->booking_code,
                        'order_reference' => $orderRef,
                        'transaction_id' => $transactionId
                    ]);
                }

                $sessionB1 = session('booking1');
                $sessionB2 = session('booking2');
                if ($sessionB1 && $sessionB2) {
                    $update = [
                        'transaction_ref_id' => $orderRef,
                        'external_ref_id' => $transactionId,
                    ];
                    if (isset($sessionB1->booking_code)) {
                        Booking::where('booking_code', $sessionB1->booking_code)->update($update);
                    }
                    if (isset($sessionB2->booking_code)) {
                        Booking::where('booking_code', $sessionB2->booking_code)->update($update);
                    }
                    Log::info('ClickPesa: Stored order reference in round-trip bookings', [
                        'order_reference' => $orderRef,
                        'transaction_id' => $transactionId,
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('ClickPesa: Could not store order reference in booking', [
                    'error' => $e->getMessage(),
                    'order_reference' => $orderRef
                ]);
            }

            // Redirect to a waiting page where user confirms payment on their phone
            try {
                return view('clickpesa.payment_waiting', [
                    'transaction_id' => $transactionId,
                    'order_id' => $orderRef,
                    'amount' => $orderDetails['amount'],
                    'status' => $status,
                    'message' => __('all.clickpesa_request_sent')
                ]);
            } catch (\Throwable $e) {
                Log::error('ClickPesa payment_waiting view failed', [
                    'order_id' => $orderRef,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return redirect()->route($this->paymentRouteForContext())
                    ->with('error', 'Could not load payment page: ' . $e->getMessage());
            }
        } else {
            // Response doesn't have expected success structure
            // Check for any error indicators first
            $errorMessage = '';
            
            if (isset($checkoutResponse->message)) {
                $errorMessage = (string) $checkoutResponse->message;
            } elseif (isset($checkoutResponse->error)) {
                $errorMessage = is_string($checkoutResponse->error) 
                    ? (string) $checkoutResponse->error 
                    : json_encode($checkoutResponse->error);
            } elseif (isset($checkoutResponse->status)) {
                $status = strtolower(trim((string) $checkoutResponse->status));
                if (in_array($status, ['failed', 'error', 'rejected', 'cancelled', 'invalid', 'declined'])) {
                    $errorMessage = isset($checkoutResponse->message) 
                        ? (string) $checkoutResponse->message 
                        : "Payment request failed with status: " . $status;
                }
            }
            
            // If no specific error message found, use generic one
            if (empty($errorMessage)) {
                $errorMessage = "Unknown error creating USSD-PUSH request";
            }

            $orderRefForLog = isset($checkoutResponse->orderReference)
                ? (string) $checkoutResponse->orderReference
                : preg_replace('/[^a-zA-Z0-9]/', '', $orderDetails['order_id']);
            
            Log::error('ClickPesa USSD-PUSH Request Failed - Invalid Response Structure', [
                'order_id' => $orderDetails['order_id'],
                'order_reference' => $orderRefForLog,
                'error' => $errorMessage,
                'response' => $checkoutResponse,
                'response_type' => gettype($checkoutResponse),
                'response_keys' => $checkoutResponse && is_object($checkoutResponse) ? array_keys((array)$checkoutResponse) : 'N/A',
                'has_id' => isset($checkoutResponse->id),
                'has_status' => isset($checkoutResponse->status),
                'has_checkout_url' => isset($checkoutResponse->checkout_url)
            ]);

            return redirect()->route($this->paymentRouteForContext())
                ->with('error', 'ClickPesa Payment Failed: ' . $errorMessage);
        }
    }

    public function VenderinitiatePayment($amount, $first_name, $last_name, $phone, $email, $order_id = null)
    {
        // Prepare order details
        $orderDetails = [
            'amount' => $amount,
            'order_id' => $order_id ?? 'ORD-' . now()->timestamp,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'phone' => $phone,
            'email' => $email,
            'redirect_url' => route('clickpesa.callback'),
            'cancel_url' => route('clickpesa.cancel'),
        ];

        // Create transaction with ClickPesa
        $checkoutResponse = $this->createCheckoutSession($orderDetails);

        // Check if response is a string (error) or object (success)
        if (is_string($checkoutResponse)) {
            $orderReference = preg_replace('/[^a-zA-Z0-9]/', '', $orderDetails['order_id']);
            Log::error('ClickPesa Checkout Creation Failed', [
                'order_id' => $orderDetails['order_id'],
                'order_reference' => $orderReference,
                'error' => $checkoutResponse,
            ]);

            // Return redirect with error message that can be caught by VenderController
            return redirect()->route('vender.pay')->with('error', 'ClickPesa Payment Failed: ' . $checkoutResponse)->withErrors(['clickpesa_error' => $checkoutResponse]);
        }

        if ($checkoutResponse && isset($checkoutResponse->checkout_url)) {
            // CRITICAL: Check for ANY error indicators before proceeding
            // Even if checkout_url exists, there might be an error message
            $hasError = false;
            $errorMessage = '';
            
            // Check for error message
            if (isset($checkoutResponse->message)) {
                $message = strtolower((string) $checkoutResponse->message);
                if (stripos($message, 'checksum') !== false ||
                    stripos($message, 'invalid') !== false ||
                    stripos($message, 'error') !== false ||
                    stripos($message, 'fail') !== false ||
                    stripos($message, 'reject') !== false) {
                    $hasError = true;
                    $errorMessage = (string) $checkoutResponse->message;
                }
            }
            
            // Check for error field
            if (!$hasError && isset($checkoutResponse->error)) {
                $hasError = true;
                $errorMessage = is_string($checkoutResponse->error) 
                    ? (string) $checkoutResponse->error 
                    : json_encode($checkoutResponse->error);
            }
            
            // Check for failed status
            if (!$hasError && isset($checkoutResponse->status)) {
                $status = strtolower(trim((string) $checkoutResponse->status));
                if (in_array($status, ['failed', 'error', 'rejected', 'cancelled', 'invalid', 'declined'])) {
                    $hasError = true;
                    $errorMessage = isset($checkoutResponse->message) 
                        ? (string) $checkoutResponse->message 
                        : "Payment failed with status: " . $status;
                }
            }
            
            if ($hasError) {
                Log::error('ClickPesa Vendor Checkout Failed - Error Detected in Response', [
                    'order_id' => $orderDetails['order_id'],
                    'error_message' => $errorMessage,
                    'full_response' => $checkoutResponse,
                    'has_checkout_url' => isset($checkoutResponse->checkout_url)
                ]);
                return redirect()->route('vender.pay')->with('error', 'ClickPesa Payment Failed: ' . $errorMessage)->withErrors(['clickpesa_error' => $errorMessage]);
            }
            
            // Validate checkout_url is not empty
            $checkoutUrl = (string) $checkoutResponse->checkout_url;
            if (empty($checkoutUrl) || !filter_var($checkoutUrl, FILTER_VALIDATE_URL)) {
                $errorMessage = "Invalid checkout URL received from ClickPesa";
                Log::error('ClickPesa Vendor Checkout Failed - Invalid URL', [
                    'order_id' => $orderDetails['order_id'],
                    'checkout_url' => $checkoutUrl,
                    'response' => $checkoutResponse
                ]);
                return redirect()->route('vender.pay')->with('error', 'ClickPesa Payment Failed: ' . $errorMessage)->withErrors(['clickpesa_error' => $errorMessage]);
            }
            
            $reference = (string) ($checkoutResponse->reference ?? preg_replace('/[^a-zA-Z0-9]/', '', $orderDetails['order_id']));

            Log::info('ClickPesa Checkout Created Successfully (Vendor)', [
                'order_id' => $orderDetails['order_id'],
                'reference' => $reference,
                'amount' => $orderDetails['amount'],
                'checkout_url_preview' => substr($checkoutUrl, 0, 50) . '...'
            ]);

            Session::put('vender', 'vender');

            // Store reference on booking so callback can find it when user returns
            $sessionBooking = session('booking');
            if ($sessionBooking && isset($sessionBooking->booking_code)) {
                try {
                    Booking::where('booking_code', $sessionBooking->booking_code)->update(['transaction_ref_id' => $reference]);
                } catch (\Exception $e) {
                    Log::warning('ClickPesa Vendor: Could not store reference on booking', ['error' => $e->getMessage()]);
                }
            }

            // Redirect to ClickPesa checkout page
            return redirect()->away($checkoutUrl);
        } else {
            // Response doesn't have checkout_url - check for error indicators
            $errorMessage = '';
            
            if (isset($checkoutResponse->message)) {
                $errorMessage = (string) $checkoutResponse->message;
            } elseif (isset($checkoutResponse->error)) {
                $errorMessage = is_string($checkoutResponse->error) 
                    ? (string) $checkoutResponse->error 
                    : json_encode($checkoutResponse->error);
            } elseif (isset($checkoutResponse->status)) {
                $status = strtolower(trim((string) $checkoutResponse->status));
                if (in_array($status, ['failed', 'error', 'rejected', 'cancelled', 'invalid', 'declined'])) {
                    $errorMessage = isset($checkoutResponse->message) 
                        ? (string) $checkoutResponse->message 
                        : "Payment failed with status: " . $status;
                }
            }
            
            // If no specific error message found, use generic one
            if (empty($errorMessage)) {
                $errorMessage = "Unknown error creating checkout session";
            }

            Log::error('ClickPesa Vendor Checkout Creation Failed - Invalid Response Structure', [
                'order_id' => $orderDetails['order_id'],
                'error' => $errorMessage,
                'response' => $checkoutResponse,
                'response_type' => gettype($checkoutResponse),
                'response_keys' => $checkoutResponse && is_object($checkoutResponse) ? array_keys((array)$checkoutResponse) : 'N/A',
                'has_checkout_url' => isset($checkoutResponse->checkout_url),
                'has_id' => isset($checkoutResponse->id),
                'has_status' => isset($checkoutResponse->status)
            ]);

            // Return redirect with error message that can be caught by VenderController
            return redirect()->route('vender.pay')->with('error', 'ClickPesa Payment Failed: ' . $errorMessage)->withErrors(['clickpesa_error' => $errorMessage]);
        }
    }


    /**
     * Handle ClickPesa callback (success and failure)
     */
    public function handleCallback(Request $request)
    {
        $reference = $request->get('reference') ?: $request->get('order_reference');
        $status = $request->get('status');

        // Some ClickPesa redirects hit callback without query params.
        // Recover the most likely reference from session/booking context so we can verify.
        if (!$reference) {
            $cachedPaymentData = Session::get('clickpesa_payment_data');
            if ($cachedPaymentData && isset($cachedPaymentData->orderReference)) {
                $reference = (string) $cachedPaymentData->orderReference;
                if (!$status && isset($cachedPaymentData->status)) {
                    $status = strtolower((string) $cachedPaymentData->status);
                }
                Log::info('ClickPesa Callback recovered reference from cached payment data', [
                    'reference' => $reference,
                    'status' => $status ?: 'unknown',
                ]);
            }
        }

        if (!$reference) {
            $sessionBooking = session('booking');
            if ($sessionBooking && isset($sessionBooking->booking_code)) {
                $booking = Booking::where('booking_code', $sessionBooking->booking_code)->first();
                if ($booking) {
                    $reference = (string) ($booking->transaction_ref_id ?: $booking->external_ref_id);
                    Log::info('ClickPesa Callback recovered reference from booking record', [
                        'booking_code' => $booking->booking_code,
                        'reference' => $reference ?: 'N/A',
                    ]);
                }
            }
        }

        // Handle explicit cancellation from user
        if ($status === 'cancelled') {
            Log::info('ClickPesa Transaction Canceled by User', [
                'reference' => $reference,
                'status' => $status,
                'query_params' => $request->all()
            ]);

            return view('clickpesa.cancel', [
                'reference' => $reference,
                'status' => $status,
                'message' => __('all.transaction_cancelled_by_user')
            ]);
        }

        // Verify transaction if reference is present - ALWAYS verify even if status says failed
        // This is critical because money may have been deducted even if status shows failed
        if ($reference) {
            // Retry verification up to 3 times for transient errors
            $verifyResponse = null;
            $lastError = null;
            
            for ($attempt = 1; $attempt <= 3; $attempt++) {
                $verifyResponse = $this->verifyTransaction($reference);
                
                // Check if we got a valid object response (not a string error)
                if (is_object($verifyResponse) && isset($verifyResponse->status)) {
                    break; // Got valid response
                }
                
                $lastError = is_string($verifyResponse) ? $verifyResponse : 'Unknown error';
                Log::warning('ClickPesa Verification Attempt Failed', [
                    'reference' => $reference,
                    'attempt' => $attempt,
                    'error' => $lastError
                ]);
                
                if ($attempt < 3) {
                    sleep(2); // Wait 2 seconds before retry
                }
            }

            // Check if verifyResponse is a valid object with status property
            if (is_object($verifyResponse) && isset($verifyResponse->status) && strtolower($verifyResponse->status) == 'success') {
                Log::info('ClickPesa Payment Verification Successful', [
                    'reference' => $reference,
                    'response' => $verifyResponse
                ]);

                $sanitizedShRef = preg_replace('/[^a-zA-Z0-9]/', '', (string) $reference);
                $specialHireOrder = SpecialHireOrder::query()
                    ->where('clickpesa_deposit_ref', $sanitizedShRef)
                    ->orWhere('clickpesa_balance_ref', $sanitizedShRef)
                    ->first();
                if ($specialHireOrder) {
                    $paySvc = app(SpecialHireOrderPaymentService::class);
                    $type = SpecialHireOrderPaymentService::resolveTypeFromReference($specialHireOrder, $sanitizedShRef);
                    if ($type) {
                        try {
                            $paySvc->confirmFromVerifiedReference($specialHireOrder, $type, $verifyResponse, $reference);
                        } catch (\Throwable $e) {
                            Log::error('Special hire ClickPesa confirm failed', [
                                'order_id' => $specialHireOrder->id,
                                'type' => $type,
                                'error' => $e->getMessage(),
                            ]);
                            return view('clickpesa.error', [
                                'message' => $e->getMessage(),
                                'reference' => $reference,
                            ]);
                        }

                        return view('clickpesa.success', [
                            'message' => __('all.payment_successful') ?: 'Payment successful',
                            'reference' => $reference,
                        ]);
                    }
                }

                $vender = Session::get('vender') ?? '';

                if (!$vender) {
                    return $this->processSuccessfulPayment($reference, $request->merchant_reference, $verifyResponse);
                } else {
                    Session::forget('vender');
                    $venderclass = new VenderWalletController();
                    return $venderclass->returned();
                }

            } else {
                // Handle verification failure - but check if it's a real failure or just API error
                $errorMessage = '';
                $actualStatus = '';
                
                if (is_object($verifyResponse) && isset($verifyResponse->status)) {
                    $actualStatus = strtolower($verifyResponse->status);
                    $errorMessage = $verifyResponse->message ?? __('all.payment_was_status', ['status' => $actualStatus]);
                } elseif (is_string($verifyResponse)) {
                    // API returned error - this is critical! Money may have been deducted
                    $errorMessage = $verifyResponse;
                    $actualStatus = 'verification_error';
                } else {
                    $errorMessage = __('all.something_went_wrong');
                    $actualStatus = 'unknown_error';
                }

                Log::error('ClickPesa Payment Verification Issue', [
                    'reference' => $reference,
                    'actual_status' => $actualStatus,
                    'error' => $errorMessage,
                    'response_type' => gettype($verifyResponse),
                    'response' => $verifyResponse,
                    'request_status' => $status
                ]);

                // If status was 'success' from redirect but verification failed, this is a CRITICAL issue
                // Money was likely deducted but we couldn't verify - show error page with support info
                if ($status === 'success' || $actualStatus === 'verification_error') {
                    return view('clickpesa.verification_error', [
                        'reference' => $reference,
                        'status' => $actualStatus,
                        'message' => __('all.payment_verification_incomplete', ['reference' => $reference]),
                        'error' => $errorMessage
                    ]);
                }

                // For actual failed/pending payments
                return view('clickpesa.cancel', [
                    'reference' => $reference,
                    'status' => $actualStatus,
                    'message' => $errorMessage
                ]);
            }
        } else {
            Log::warning('No Reference in ClickPesa Callback', [
                'query_params' => $request->all()
            ]);

            return view('clickpesa.cancel', [
                'reference' => 'N/A',
                'status' => 'error',
                'message' => __('all.no_reference_contact_support')
            ]);
        }
    }

    /**
     * Retry ClickPesa payment (push again) from cancel/error page.
     * Finds booking by reference and re-sends USSD push.
     */
    public function retryPayment(Request $request)
    {
        $reference = $request->get('reference');
        if (!$reference || $reference === 'N/A') {
            return redirect()->route('home')->with('error', __('all.session_expired_try_again') ?? 'Session expired. Please start again from home.');
        }

        $sanitized = preg_replace('/[^a-zA-Z0-9]/', '', $reference);
        $booking = Booking::where('transaction_ref_id', $reference)
            ->orWhere('transaction_ref_id', $sanitized)
            ->orWhere('external_ref_id', $reference)
            ->orWhere('external_ref_id', $sanitized)
            ->first();

        if (!$booking) {
            return redirect()->route('home')->with('error', __('all.booking_not_found_try_again') ?? 'Booking not found. Please start again from home.');
        }

        if (!in_array($booking->payment_status, ['Unpaid', 'resaved'], true)) {
            return redirect()->route('home')->with('success', __('all.payment_already_completed') ?? 'Payment already completed.');
        }

        $name = $booking->customer_name ?? 'Customer';
        $parts = explode(' ', trim($name), 2);
        $firstName = $parts[0] ?? 'Customer';
        $lastName = $parts[1] ?? '';
        $phone = $booking->customer_phone ?? '';
        $email = $booking->customer_email ?? '';

        $orderId = $booking->booking_code . '-' . time();
        $orderDetails = [
            'amount' => (int) round($booking->amount),
            'order_id' => $orderId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $phone,
            'email' => $email,
            'redirect_url' => route('clickpesa.callback'),
            'cancel_url' => route('clickpesa.cancel'),
        ];

        $checkoutResponse = $this->createCheckoutSession($orderDetails);

        if (is_string($checkoutResponse)) {
            Log::warning('ClickPesa retry failed', ['reference' => $reference, 'error' => $checkoutResponse]);
            return view('clickpesa.error', [
                'message' => $checkoutResponse,
                'reference' => $reference
            ]);
        }

        if (!($checkoutResponse && isset($checkoutResponse->id) && isset($checkoutResponse->status))) {
            $msg = isset($checkoutResponse->message) ? (string) $checkoutResponse->message : 'Unknown error. Please try again.';
            return view('clickpesa.error', ['message' => $msg, 'reference' => $reference]);
        }

        $transactionId = (string) $checkoutResponse->id;
        $status = (string) $checkoutResponse->status;
        $orderRef = isset($checkoutResponse->orderReference) ? (string) $checkoutResponse->orderReference : preg_replace('/[^a-zA-Z0-9]/', '', $orderId);

        Booking::where('id', $booking->id)->update([
            'transaction_ref_id' => $orderRef,
            'external_ref_id' => $transactionId
        ]);
        session()->put('booking', $booking->fresh());

        return view('clickpesa.payment_waiting', [
            'transaction_id' => $transactionId,
            'order_id' => $orderRef,
            'amount' => $orderDetails['amount'],
            'status' => $status,
            'message' => __('all.clickpesa_request_sent_retry')
        ]);
    }

    /**
     * Handle cancellation specifically
     */
    public function handleCancel(Request $request)
    {
        $reference = $request->get('reference');
        $status = $request->get('status');

        Log::info('ClickPesa Transaction Canceled (Direct)', [
            'reference' => $reference,
            'status' => $status,
            'query_params' => $request->all()
        ]);

        return view('clickpesa.cancel', [
            'reference' => $reference,
            'status' => $status,
            'message' => 'Transaction was canceled'
        ]);
    }

    /**
     * Get ClickPesa access token (with retries and configurable timeout).
     * Use CLICKPESA_TIMEOUT and CLICKPESA_CONNECT_TIMEOUT in .env if API is slow or far.
     */
    private function getAccessToken()
    {
        // Check if credentials are set
        if (empty($this->apiKey) || empty($this->clientId)) {
            Log::error('ClickPesa Token Error - Missing Credentials', [
                'api_key_set' => !empty($this->apiKey),
                'client_id_set' => !empty($this->clientId)
            ]);
            return null;
        }

        $tokenEndpoint = 'https://api.clickpesa.com/third-parties/generate-token';
        $timeout = (int) env('CLICKPESA_TIMEOUT', 45);       // total request timeout (seconds)
        $connectTimeout = (int) env('CLICKPESA_CONNECT_TIMEOUT', 15); // connection timeout (seconds)
        $maxAttempts = min(3, max(1, (int) env('CLICKPESA_TOKEN_RETRIES', 3)));

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $tokenEndpoint);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connectTimeout);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'api-key: ' . $this->apiKey,
                'client-id: ' . $this->clientId
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if (!$curlError && $httpCode === 200 && !empty($response)) {
                $token = $this->parseTokenFromResponse($response);
                if ($token !== null) {
                    return $token;
                }
            }

            // Log and retry on failure (timeout, connection error, or non-200)
            Log::warning('ClickPesa Token attempt failed', [
                'attempt' => $attempt,
                'max_attempts' => $maxAttempts,
                'curl_error' => $curlError,
                'http_code' => $httpCode,
                'endpoint' => $tokenEndpoint
            ]);
            if ($curlError) {
                Log::error('ClickPesa Token CURL Error', [
                    'curl_error' => $curlError,
                    'endpoint' => $tokenEndpoint
                ]);
            }

            if ($attempt < $maxAttempts) {
                usleep(500000); // 0.5s delay before retry
            }
        }

        return null;
    }

    /**
     * Parse access token from ClickPesa generate-token JSON response.
     */
    private function parseTokenFromResponse($response)
    {
        $jsonResponse = json_decode($response);
        $token = null;

        // Format 1: {"success":true,"token":"Bearer eyJ..."}
        if (isset($jsonResponse->success) && $jsonResponse->success && isset($jsonResponse->token)) {
            $token = $jsonResponse->token;
        }
        // Format 2: {"token":"Bearer ..."} or {"token":"..."}
        elseif (isset($jsonResponse->token)) {
            $token = $jsonResponse->token;
        }
        // Format 3: {"access_token":"..."}
        elseif (isset($jsonResponse->access_token)) {
            $token = $jsonResponse->access_token;
        }
        // Format 4: Array response [{"token":"..."}]
        elseif (is_array($jsonResponse) && isset($jsonResponse[0]->token)) {
            $token = $jsonResponse[0]->token;
        }

        if ($token) {
            if (strpos($token, 'Bearer ') === 0) {
                $token = substr($token, 7);
            }
            Log::debug('ClickPesa Token Generated Successfully');
            return $token;
        }

        Log::error('ClickPesa Token Response Invalid', [
            'response' => $jsonResponse,
            'raw_response' => $response,
            'response_keys' => is_object($jsonResponse) ? array_keys((array)$jsonResponse) : 'not_object'
        ]);
        return null;
    }

    /**
     * Recursively canonicalize payload for consistent ordering (per ClickPesa docs).
     * - Objects: keys sorted alphabetically at every level.
     * - Arrays (sequential list): preserve order, canonicalize each element.
     * @see https://docs.clickpesa.com/home/checksum
     */
    private function canonicalize($obj)
    {
        if ($obj === null || !is_array($obj)) {
            return $obj;
        }

        if (array_values($obj) === $obj) {
            return array_map([$this, 'canonicalize'], $obj);
        }

        ksort($obj);
        $result = [];
        foreach ($obj as $key => $value) {
            $result[$key] = $this->canonicalize($value);
        }
        return $result;
    }

    /**
     * Create payload checksum (HMAC-SHA256 of canonical JSON).
     * Per docs: canonicalize → serialize to JSON → HMAC-SHA256 → hex digest.
     * Caller must pass payload without 'checksum'/'checksumMethod' when those would otherwise be present.
     * @see https://docs.clickpesa.com/home/checksum
     */
    private function createPayloadChecksum(string $checksumKey, array $payload): string
    {
        $canonicalPayload = $this->canonicalize($payload);
        $payloadString = json_encode($canonicalPayload, JSON_UNESCAPED_SLASHES);
        return hash_hmac('sha256', $payloadString, $checksumKey);
    }

    /**
     * Validate received checksum (e.g. from webhook/callback body).
     * Excludes checksum and checksumMethod from payload before recomputing; uses hash_equals for comparison.
     * @see https://docs.clickpesa.com/home/checksum
     */
    private function validateChecksum(string $checksumKey, array $payload, string $receivedChecksum): bool
    {
        if (empty($receivedChecksum)) {
            return false;
        }
        $payloadForValidation = $payload;
        unset($payloadForValidation['checksum'], $payloadForValidation['checksumMethod']);
        $computedChecksum = $this->createPayloadChecksum($checksumKey, $payloadForValidation);
        return hash_equals($computedChecksum, $receivedChecksum);
    }

    /**
     * Compute checksum for outbound request (wrapper around createPayloadChecksum).
     */
    private function computeChecksum(array $payload): string
    {
        $checksumKey = env('CLICKPESA_CHECKSUM_KEY', $this->clientId);
        return $this->createPayloadChecksum($checksumKey ?? '', $payload);
    }

    /**
     * Create ClickPesa USSD-PUSH request
     */
    public function createCheckoutSession($orderDetails)
    {
        // Get access token first
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return "Failed to obtain access token from ClickPesa";
        }

        $rawPhone = isset($orderDetails['phone']) ? (string) $orderDetails['phone'] : '';
        $normalized = self::normalizeTanzaniaMsisdnForClickPesa($rawPhone);
        if (!$normalized['ok']) {
            return $normalized['error'] ?? 'Invalid phone number for ClickPesa.';
        }
        $phoneNumber = $normalized['phone'];

        // ClickPesa USSD minimum/maximum (API returns 400 outside this range, e.g. "Amount must be between 908 and 3000000")
        $amountNum = (float) ($orderDetails['amount'] ?? 0);
        $minTzs = (float) env('CLICKPESA_MIN_AMOUNT_TZS', 908);
        $maxTzs = (float) env('CLICKPESA_MAX_AMOUNT_TZS', 3000000);
        if ($amountNum < $minTzs) {
            return "Amount must be at least {$minTzs} TZS for mobile money (ClickPesa minimum). "
                . 'Increase the ticket total (e.g. minimum fare) or use another payment method.';
        }
        if ($amountNum > $maxTzs) {
            return "Amount must not exceed {$maxTzs} TZS (ClickPesa maximum).";
        }

        // ClickPesa requires alphanumeric-only order reference (no hyphens or special chars)
        $orderReference = preg_replace('/[^a-zA-Z0-9]/', '', $orderDetails['order_id']);

        // ClickPesa USSD-PUSH: only required fields (amount, currency, orderReference, phoneNumber) per API spec.
        // Checksum must be computed over exactly these fields; do not include extra fields or checksum will fail.
        $payload = [
            'amount' => (string) $orderDetails['amount'],
            'currency' => 'TZS',
            'orderReference' => $orderReference,
            'phoneNumber' => $phoneNumber,
        ];

        // Generate checksum (required when checksum is enabled in ClickPesa app)
        // CRITICAL: Do NOT make API call if checksum fails - this prevents push notifications
        if (empty($this->checksumKey)) {
            Log::error('ClickPesa checksum key is not set - checksum cannot be computed - ABORTING API CALL', [
                'order_id' => $orderDetails['order_id'],
                'order_reference' => $orderReference
            ]);
            return "ClickPesa checksum key is not configured. Set CLICKPESA_CHECKSUM_KEY in .env.";
        }

        try {
            $computedChecksum = $this->computeChecksum($payload);
            if (empty($computedChecksum) || strlen($computedChecksum) < 10) {
                Log::error('ClickPesa checksum computation returned invalid value - ABORTING API CALL', [
                    'order_id' => $orderDetails['order_id'],
                    'order_reference' => $orderReference,
                    'checksum_length' => strlen($computedChecksum ?? '')
                ]);
                return "Failed to create checksum. Please check CLICKPESA_CHECKSUM_KEY configuration.";
            }
            $payload['checksum'] = $computedChecksum;
            Log::debug('ClickPesa checksum computed successfully', [
                'order_id' => $orderDetails['order_id'],
                'order_reference' => $orderReference,
                'checksum_length' => strlen($computedChecksum)
            ]);
        } catch (\Exception $e) {
            Log::error('ClickPesa checksum computation failed with exception - ABORTING API CALL', [
                'order_id' => $orderDetails['order_id'],
                'order_reference' => $orderReference,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return "Failed to create checksum: " . $e->getMessage();
        }

        $checksumKey = env('CLICKPESA_CHECKSUM_KEY', $this->clientId);
        $canonicalForLogging = $this->canonicalize(array_diff_key($payload, ['checksum' => 1]));
        Log::debug('ClickPesa Checksum Computed', [
            'checksum' => $computedChecksum,
            'canonical_json' => json_encode($canonicalForLogging, JSON_UNESCAPED_SLASHES),
            'checksum_key_source' => env('CLICKPESA_CHECKSUM_KEY') ? 'CLICKPESA_CHECKSUM_KEY' : 'CLICKPESA_CLIENT_ID',
        ]);

        $jsonPayload = json_encode($payload);

        Log::debug('ClickPesa USSD-PUSH Request', [
            'order_id' => $orderDetails['order_id'],
            'order_reference' => $orderReference,
            'endpoint' => $this->endpoint,
            'payload' => $payload,
            'phone_formatted' => $phoneNumber,
            'token_preview' => substr($accessToken, 0, 20) . '...'
        ]);

        Log::info('ClickPesa payment request', [
            'order_reference' => $orderReference,
            'order_id' => $orderDetails['order_id'],
            'amount' => $orderDetails['amount'],
            'currency' => $payload['currency'] ?? 'TZS',
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->endpoint);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlInfo = curl_getinfo($ch);
        curl_close($ch);

        // Log the raw response for debugging
        Log::debug('ClickPesa Raw API Response', [
            'http_code' => $httpCode,
            'response' => $response,
            'curl_error' => $curlError,
            'order_id' => $orderDetails['order_id'],
            'order_reference' => $orderReference
        ]);

        if ($httpCode != 200 && $httpCode != 201) {
            Log::error('ClickPesa Create Checkout HTTP Error', [
                'http_code' => $httpCode,
                'order_id' => $orderDetails['order_id'],
                'order_reference' => $orderReference,
                'response' => $response,
                'curl_error' => $curlError,
                'endpoint' => $this->endpoint,
                'curl_info' => $curlInfo
            ]);
            
            // Try to parse error message from response
            $errorMessage = "HTTP Error $httpCode";
            $jsonError = json_decode($response);
            if ($jsonError && isset($jsonError->message)) {
                // message can be string or array; normalize to readable string
                if (is_array($jsonError->message)) {
                    $errorMessage = implode(' ', array_map('strval', $jsonError->message));
                } elseif (is_string($jsonError->message)) {
                    $errorMessage = $jsonError->message;
                } else {
                    $errorMessage = json_encode($jsonError->message);
                }
            } elseif (!empty($response)) {
                $errorMessage = "API Error: " . substr($response, 0, 200);
            }
            
            return $errorMessage;
        }

        $jsonResponse = json_decode($response);
        if ($jsonResponse === null) {
            Log::error('ClickPesa Create Checkout JSON Parse Error', [
                'response' => $response,
                'order_id' => $orderDetails['order_id'],
                'order_reference' => $orderReference
            ]);
            return "Error parsing JSON response: $response";
        }

        Log::debug('ClickPesa Create Checkout Response', [
            'order_id' => $orderDetails['order_id'],
            'order_reference' => $orderReference,
            'response' => $jsonResponse
        ]);

        // CRITICAL: Check for ANY error indicators in response BEFORE proceeding
        // Check for error message (even if other fields exist)
        if (isset($jsonResponse->message)) {
            $message = strtolower((string) $jsonResponse->message);
            if (stripos($message, 'checksum') !== false ||
                stripos($message, 'invalid') !== false ||
                stripos($message, 'error') !== false ||
                stripos($message, 'fail') !== false ||
                stripos($message, 'reject') !== false ||
                stripos($message, 'unauthorized') !== false) {
                $errorMessage = (string) $jsonResponse->message;
                Log::error('ClickPesa API Returned Error Message', [
                    'order_id' => $orderDetails['order_id'],
                    'order_reference' => $orderReference,
                    'error_message' => $errorMessage,
                    'full_response' => $jsonResponse
                ]);
                return "ClickPesa error: " . $errorMessage;
            }
        }

        // Check for error field
        if (isset($jsonResponse->error)) {
            $errorMessage = is_string($jsonResponse->error) 
                ? (string) $jsonResponse->error 
                : json_encode($jsonResponse->error);
            Log::error('ClickPesa API Returned Error Field', [
                'order_id' => $orderDetails['order_id'],
                'order_reference' => $orderReference,
                'error' => $errorMessage,
                'full_response' => $jsonResponse
            ]);
            return "ClickPesa error: " . $errorMessage;
        }

        // Check if status indicates failure (even if id exists)
        if (isset($jsonResponse->status)) {
            $status = strtolower(trim((string) $jsonResponse->status));
            if (in_array($status, ['failed', 'error', 'rejected', 'cancelled', 'invalid', 'declined'])) {
                $errorMessage = isset($jsonResponse->message) 
                    ? (string) $jsonResponse->message 
                    : "Payment request failed with status: " . $status;
                Log::error('ClickPesa Payment Request Failed - Invalid Status', [
                    'order_id' => $orderDetails['order_id'],
                    'order_reference' => $orderReference,
                    'status' => $status,
                    'error_message' => $errorMessage,
                    'full_response' => $jsonResponse
                ]);
                return $errorMessage;
            }
        }

        // For USSD-PUSH: Check if we have required success indicators
        // If response doesn't have id/status or checkout_url, it's likely an error
        $hasSuccessIndicator = false;
        $successStatus = null;
        
        if (isset($jsonResponse->id) && isset($jsonResponse->status)) {
            $status = strtolower(trim((string) $jsonResponse->status));
            if (in_array($status, ['pending', 'initiated', 'processing', 'success', 'completed'])) {
                $hasSuccessIndicator = true;
                $successStatus = $status;
            } else {
                // Status exists but indicates failure
                $errorMessage = isset($jsonResponse->message) 
                    ? (string) $jsonResponse->message 
                    : "Payment request failed with status: " . $status;
                Log::error('ClickPesa Response Has Failed Status', [
                    'order_id' => $orderDetails['order_id'],
                    'order_reference' => $orderReference,
                    'status' => $status,
                    'response' => $jsonResponse,
                    'error_message' => $errorMessage
                ]);
                return $errorMessage;
            }
        } elseif (isset($jsonResponse->checkout_url)) {
            // Validate checkout_url is actually a valid URL
            $checkoutUrl = (string) $jsonResponse->checkout_url;
            if (!empty($checkoutUrl) && filter_var($checkoutUrl, FILTER_VALIDATE_URL)) {
                $hasSuccessIndicator = true;
            }
        }

        if (!$hasSuccessIndicator) {
            $errorMessage = isset($jsonResponse->message) 
                ? (string) $jsonResponse->message 
                : (isset($jsonResponse->error) 
                    ? (is_string($jsonResponse->error) ? (string) $jsonResponse->error : json_encode($jsonResponse->error))
                    : "Unknown error creating checkout session");
            Log::error('ClickPesa Response Missing Success Indicators', [
                'order_id' => $orderDetails['order_id'],
                'order_reference' => $orderReference,
                'response' => $jsonResponse,
                'response_type' => gettype($jsonResponse),
                'has_id' => isset($jsonResponse->id),
                'has_status' => isset($jsonResponse->status),
                'has_checkout_url' => isset($jsonResponse->checkout_url),
                'error_message' => $errorMessage
            ]);
            return $errorMessage;
        }

        // Final validation: Log successful response for debugging
        Log::info('ClickPesa API Call Successful', [
            'order_id' => $orderDetails['order_id'],
            'order_reference' => $orderReference,
            'has_id' => isset($jsonResponse->id),
            'has_status' => isset($jsonResponse->status),
            'has_checkout_url' => isset($jsonResponse->checkout_url),
            'status' => $successStatus ?? 'N/A'
        ]);

        return $jsonResponse;
    }

    /**
     * Check payment status via AJAX polling
     * Called from the waiting page to check if payment is complete
     */
    public function checkPaymentStatus(Request $request)
    {
        $orderReference = $request->get('order_reference');
        
        if (!$orderReference) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Order reference is required'
            ], 400);
        }

        // ClickPesa API expects alphanumeric order reference (same as when creating payment)
        $orderRefForApi = preg_replace('/[^a-zA-Z0-9]/', '', $orderReference);

        // Get access token
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            Log::warning('ClickPesa Status Check - Token Error', [
                'order_reference' => $orderReference
            ]);
            // Don't return error, return pending so client retries
            return response()->json([
                'success' => false,
                'status' => 'pending',
                'message' => 'Checking payment status...'
            ]);
        }

        // Call ClickPesa API to check payment status (use sanitized ref to match create payload)
        $checkUrl = 'https://api.clickpesa.com/third-parties/payments/' . $orderRefForApi;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $checkUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15); // Set timeout
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        Log::debug('ClickPesa Payment Status Check', [
            'order_reference' => $orderReference,
            'http_code' => $httpCode,
            'response' => $response,
            'curl_error' => $curlError ?: 'none'
        ]);

        // Handle network/API errors - return pending so client retries
        if ($curlError || $httpCode != 200) {
            Log::warning('ClickPesa Status Check API Error', [
                'order_reference' => $orderReference,
                'http_code' => $httpCode,
                'curl_error' => $curlError
            ]);
            return response()->json([
                'success' => false,
                'status' => 'pending',
                'message' => 'Checking payment status, please wait...'
            ]);
        }

        $jsonResponse = json_decode($response);
        
        if ($jsonResponse === null) {
            Log::warning('ClickPesa Status Check Parse Error', [
                'order_reference' => $orderReference,
                'response' => $response
            ]);
            return response()->json([
                'success' => false,
                'status' => 'pending',
                'message' => 'Checking payment status...'
            ]);
        }

        // API may return array, object, or { data: [...] } - get first payment item
        $paymentData = null;
        if (is_array($jsonResponse)) {
            $paymentData = $jsonResponse[0] ?? null;
        } elseif (isset($jsonResponse->data) && is_array($jsonResponse->data)) {
            $paymentData = $jsonResponse->data[0] ?? null;
        } elseif (isset($jsonResponse->data) && is_object($jsonResponse->data)) {
            $paymentData = $jsonResponse->data;
        } elseif (is_object($jsonResponse) && isset($jsonResponse->status)) {
            $paymentData = $jsonResponse;
        } elseif (is_object($jsonResponse)) {
            $paymentData = $jsonResponse;
        }
        
        if (!$paymentData) {
            return response()->json([
                'success' => false,
                'status' => 'pending',
                'message' => __('all.waiting_for_payment_confirmation')
            ]);
        }

        $status = strtoupper($paymentData->status ?? 'PENDING');
        
        Log::info('ClickPesa Payment Status', [
            'order_reference' => $orderReference,
            'status' => $status,
            'collected_amount' => $paymentData->collectedAmount ?? 0
        ]);
        
        if (self::clickPesaPaidStatus($status)) {
            // Payment successful - store payment data in session for callback processing
            Session::put('clickpesa_payment_data', $paymentData);
            
            // Also store in database as backup (in case session is lost)
            try {
                $booking = session('booking');
                if ($booking && isset($booking->booking_code)) {
                    Booking::where('booking_code', $booking->booking_code)
                        ->update([
                            'transaction_ref_id' => $orderRefForApi,
                            'external_ref_id' => $paymentData->id ?? $orderRefForApi
                        ]);
                }
            } catch (\Exception $e) {
                Log::warning('Could not update booking with transaction ref', [
                    'error' => $e->getMessage(),
                    'order_reference' => $orderReference
                ]);
            }

            return response()->json([
                'success' => true,
                'status' => 'success',
                'message' => __('all.payment_completed_successfully'),
                'redirect_url' => route('clickpesa.callback', [
                    'reference' => $orderRefForApi,
                    'status' => 'success'
                ])
            ]);
        } elseif ($status === 'FAILED' || $status === 'CANCELLED' || $status === 'REJECTED') {
            return response()->json([
                'success' => false,
                'status' => strtolower($status),
                'message' => $paymentData->message ?? __('all.payment_was_status', ['status' => strtolower($status)]),
                'redirect_url' => route('clickpesa.cancel', [
                    'reference' => $orderReference,
                    'status' => strtolower($status)
                ])
            ]);
        } else {
            // Still pending (INITIATED, PENDING, PROCESSING, etc.)
            return response()->json([
                'success' => false,
                'status' => 'pending',
                'message' => __('all.waiting_for_payment_confirmation')
            ]);
        }
    }

    /**
     * Verify ClickPesa transaction
     * Returns object with 'status' property on success, or error object on failure
     */
    public function verifyTransaction($reference)
    {
        // First check if we have cached payment data from AJAX polling
        $cachedPaymentData = Session::get('clickpesa_payment_data');
        if ($cachedPaymentData && isset($cachedPaymentData->orderReference) && $cachedPaymentData->orderReference === $reference) {
            // Clear the cached data
            Session::forget('clickpesa_payment_data');
            
            Log::info('ClickPesa Using Cached Payment Data', [
                'reference' => $reference,
                'status' => $cachedPaymentData->status ?? 'unknown'
            ]);

            $cachedRaw = (string) ($cachedPaymentData->status ?? 'pending');
            $cachedNorm = self::clickPesaPaidStatus($cachedRaw) ? 'success' : strtolower(trim($cachedRaw));

            // Transform to expected format for compatibility
            return (object) [
                'status' => $cachedNorm,
                'reference' => $cachedPaymentData->orderReference ?? $reference,
                'amount' => $cachedPaymentData->collectedAmount ?? 0,
                'message' => $cachedPaymentData->message ?? 'Payment verified',
                'original_response' => $cachedPaymentData
            ];
        }

        // Get access token for API call
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            Log::error('ClickPesa Verify Transaction - Failed to get access token', [
                'reference' => $reference
            ]);
            // Return error object instead of string for consistent handling
            return (object) [
                'status' => 'api_error',
                'reference' => $reference,
                'amount' => 0,
                'message' => 'Failed to obtain access token',
                'error_type' => 'token_error'
            ];
        }

        // ClickPesa API expects alphanumeric reference (same as when creating)
        $refForApi = preg_replace('/[^a-zA-Z0-9]/', '', $reference);
        $checkUrl = 'https://api.clickpesa.com/third-parties/payments/' . $refForApi;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $checkUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Set 30 second timeout
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // Connection timeout
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            Log::error('ClickPesa Verify Transaction CURL Error', [
                'reference' => $reference,
                'curl_error' => $curlError
            ]);
            return (object) [
                'status' => 'api_error',
                'reference' => $reference,
                'amount' => 0,
                'message' => 'Network error: ' . $curlError,
                'error_type' => 'curl_error'
            ];
        }

        if ($httpCode != 200) {
            Log::error('ClickPesa Verify Transaction HTTP Error', [
                'http_code' => $httpCode,
                'reference' => $reference,
                'response' => $response
            ]);
            
            // Try to parse error response for more info
            $errorData = json_decode($response);
            $errorMessage = 'HTTP Error ' . $httpCode;
            if ($errorData && isset($errorData->message)) {
                $errorMessage = $errorData->message;
            }
            
            return (object) [
                'status' => 'api_error',
                'reference' => $reference,
                'amount' => 0,
                'message' => $errorMessage,
                'http_code' => $httpCode,
                'error_type' => 'http_error'
            ];
        }

        $jsonResponse = json_decode($response);
        if ($jsonResponse === null) {
            Log::error('ClickPesa Verify Transaction JSON Parse Error', [
                'response' => $response,
                'reference' => $reference
            ]);
            return (object) [
                'status' => 'api_error',
                'reference' => $reference,
                'amount' => 0,
                'message' => 'Error parsing API response',
                'error_type' => 'parse_error'
            ];
        }

        // API may return array, object, or { data: [...] } — align with checkPaymentStatus()
        $paymentData = null;
        if (is_array($jsonResponse)) {
            $paymentData = $jsonResponse[0] ?? null;
        } elseif (isset($jsonResponse->data) && is_array($jsonResponse->data)) {
            $paymentData = $jsonResponse->data[0] ?? null;
        } elseif (isset($jsonResponse->data) && is_object($jsonResponse->data)) {
            $paymentData = $jsonResponse->data;
        } elseif (is_object($jsonResponse) && isset($jsonResponse->status)) {
            $paymentData = $jsonResponse;
        } elseif (is_object($jsonResponse)) {
            $paymentData = $jsonResponse;
        }

        if (!$paymentData || !is_object($paymentData)) {
            Log::error('ClickPesa Verify Transaction - No payment data', [
                'reference' => $reference,
                'response' => $jsonResponse
            ]);
            return (object) [
                'status' => 'not_found',
                'reference' => $reference,
                'amount' => 0,
                'message' => 'No payment data found for this reference',
                'error_type' => 'not_found'
            ];
        }

        Log::info('ClickPesa Verify Transaction Response', [
            'reference' => $reference,
            'api_status' => $paymentData->status ?? 'unknown',
            'collected_amount' => $paymentData->collectedAmount ?? 0
        ]);

        $rawStatus = (string) ($paymentData->status ?? 'pending');
        $normalizedStatus = self::clickPesaPaidStatus($rawStatus) ? 'success' : strtolower(trim($rawStatus));

        // Transform to expected format for compatibility (callers expect status === 'success' when paid)
        return (object) [
            'status' => $normalizedStatus,
            'reference' => $paymentData->orderReference ?? $reference,
            'amount' => $paymentData->collectedAmount ?? 0,
            'message' => $paymentData->message ?? 'Payment verified',
            'original_response' => $paymentData
        ];
    }

    /**
     * When the booking is already Paid (e.g. duplicate callback), redirect like DPO/_redirect
     * instead of leaving the user on a static ClickPesa success page.
     */
    private function redirectAfterClickPesaAlreadyPaid(Booking $booking): \Illuminate\Http\RedirectResponse
    {
        $message = __('all.payment_successful') ?: 'Payment successful';
        if (auth()->check() && auth()->user()->role === 'customer') {
            return redirect()->route('customer.mybooking')->with('success', $message);
        }

        return redirect()->route('booking.status', $booking->id)->with('success', $message);
    }

    private function processSuccessfulPayment($transToken, $companyRef, $verifyResponse)
    {
        // Retrieve booking using CompanyRef (which should be booking_code)
        $booking1 = session()->get('booking1');
        $booking2 = session()->get('booking2');
        if (!is_null($booking1) && !is_null($booking2)) {
            $round = new RoundpaymentController();
            $code1 = $booking1->booking_code ?? 'N/A';
            $code2 = $booking2->booking_code ?? 'N/A';

            try {
                $data1 = $round->roundtrip($transToken, $transToken, $verifyResponse, $code1, 'clickpesa');
                $data2 = $round->roundtrip($transToken, $transToken, $verifyResponse, $code2, 'clickpesa');

                if (is_array($data1) && isset($data1['errorMessage'])) {
                    session()->forget(['booking1', 'booking2', 'is_round', 'booking_form']);
                    return view('clickpesa.error', [
                        'message' => $data1['errorMessage'] ?? __('all.booking_not_found'),
                        'reference' => $transToken
                    ]);
                }
                if (is_array($data2) && isset($data2['errorMessage'])) {
                    session()->forget(['booking1', 'booking2', 'is_round', 'booking_form']);
                    return view('clickpesa.error', [
                        'message' => $data2['errorMessage'] ?? __('all.booking_not_found'),
                        'reference' => $transToken
                    ]);
                }

                // Clear round trip session data after successful processing
                session()->forget(['booking1', 'booking2', 'is_round', 'booking_form']);

                $red = new RedirectController();
                return $red->showRoundTripBookingStatus($data1, $data2);
            } catch (\Exception $e) {
                Log::error('Round trip payment processing failed in processSuccessfulPayment', [
                    'error' => $e->getMessage(),
                    'booking1_code' => $code1,
                    'booking2_code' => $code2,
                    'transaction_token' => $transToken
                ]);

                // Clear session data on error
                session()->forget(['booking1', 'booking2', 'is_round', 'booking_form']);

                return view('clickpesa.error', [
                    'message' => __('all.round_trip_payment_process_failed', ['error' => $e->getMessage()]),
                    'reference' => $transToken
                ]);
            }
        }

        // Session lost: settle every unpaid leg that shares this ClickPesa order ref.
        $refCandidates = array_values(array_unique(array_filter([
            $transToken,
            $transToken ? preg_replace('/[^a-zA-Z0-9]/', '', $transToken) : null,
        ])));
        foreach ($refCandidates as $ref) {
            $roundByRef = (new RoundpaymentController())->settleAllByTransactionRef($ref, $verifyResponse, 'clickpesa');
            if (is_array($roundByRef) && isset($roundByRef['errorMessage'])) {
                return view('clickpesa.error', [
                    'message' => $roundByRef['errorMessage'] ?? __('all.booking_not_found'),
                    'reference' => $transToken,
                ]);
            }
            if (is_array($roundByRef) && count($roundByRef) >= 2) {
                session()->forget(['booking1', 'booking2', 'is_round', 'booking_form']);
                $red = new RedirectController();
                return $red->showRoundTripBookingStatus($roundByRef[0], $roundByRef[1]);
            }
        }

        // Try to get booking from session first
        $booking = null;
        $sessionBooking = session('booking');
        
        if ($sessionBooking && isset($sessionBooking->booking_code)) {
            $code = $sessionBooking->booking_code;
            $booking = Booking::where('booking_code', $code)->first();
            Log::info('ClickPesa: Found booking from session', ['booking_code' => $code]);
        }
        
        // If session was lost, try to find booking by transaction reference
        if (!$booking && $transToken) {
            $booking = Booking::where('transaction_ref_id', $transToken)->first();
            if (!$booking) {
                $booking = Booking::where('external_ref_id', $transToken)->first();
            }
            // ClickPesa uses alphanumeric ref; try sanitized if URL had hyphen (e.g. old link)
            if (!$booking) {
                $sanitizedRef = preg_replace('/[^a-zA-Z0-9]/', '', $transToken);
                if ($sanitizedRef !== $transToken) {
                    $booking = Booking::where('transaction_ref_id', $sanitizedRef)->first();
                    if (!$booking) {
                        $booking = Booking::where('external_ref_id', $sanitizedRef)->first();
                    }
                }
            }
            if ($booking) {
                Log::info('ClickPesa: Found booking by transaction reference', [
                    'transaction_ref' => $transToken,
                    'booking_code' => $booking->booking_code
                ]);
            }
        }
        
        // If still no booking and we have companyRef, try that
        if (!$booking && $companyRef) {
            // companyRef might be the booking_code (sanitized)
            $booking = Booking::where('booking_code', $companyRef)->first();
            
            if (!$booking) {
                // Try with wildcard in case it was sanitized
                $booking = Booking::where('booking_code', 'LIKE', '%' . $companyRef . '%')->first();
            }
            
            if ($booking) {
                Log::info('ClickPesa: Found booking by company reference', [
                    'company_ref' => $companyRef,
                    'booking_code' => $booking->booking_code
                ]);
            }
        }

        if (!$booking) {
            Log::error('Booking not found', [
                'order_reference' => $transToken,
                'company_ref' => $companyRef,
            ]);
            return [
                'errorMessage' => 'Booking not found',
                'reference' => $transToken
            ];
        }

        // Check for duplicate processing (allow Unpaid and resaved to be paid)
        if (!in_array($booking->payment_status, ['Unpaid', 'resaved'], true)) {
            Log::warning('Booking already processed', [
                'order_reference' => $transToken,
                'company_ref' => $companyRef,
                'booking_code' => $booking->booking_code,
            ]);

            return $this->redirectAfterClickPesaAlreadyPaid($booking);
        }

        // Begin transaction
        DB::beginTransaction();

        try {
            $settlementService = app(BookingSettlementService::class);
            $settled = $settlementService->settlePaidBooking($booking, [
                'trans_status' => 'success',
                'trans_token' => $transToken,
                'payment_method' => 'clickpesa',
                'cancel_amount' => Session::get('cancel', 0),
            ]);
            $booking = $settled['booking'];
            $bus = $settled['bus'];
            $busOwnerAmount = $settled['bus_owner_amount'];
            $systemBalanceAmount = $settled['system_balance_amount'];
            $paymentFeesAmount = $settled['payment_fees_amount'];
            $bimaAmount = $booking->bima_amount ?? 0;

            DB::commit();

            // --- TRA INTEGRATION ---
            try {
                Log::info('TRA Fiscalization Starting (ClickPesa Payment)', [
                    'booking_id' => $booking->id,
                    'booking_code' => $booking->booking_code,
                    'payment_method' => 'clickpesa',
                    'amount' => $booking->amount,
                    'transaction_token' => $transToken,
                ]);
                
                $tra = new \App\Services\TraVfdService();
                $fiscalized = $tra->fiscalize($booking->refresh());
                
                if ($fiscalized) {
                    Log::info('TRA Fiscalization Successful (ClickPesa Payment)', [
                        'booking_id' => $booking->id,
                        'booking_code' => $booking->booking_code,
                        'tra_status' => $booking->tra_status,
                        'tra_vnum' => $booking->tra_vnum ?? 'N/A',
                    ]);
                } else {
                    Log::warning('TRA Fiscalization Returned False (ClickPesa Payment)', [
                        'booking_id' => $booking->id,
                        'booking_code' => $booking->booking_code,
                        'tra_status' => $booking->tra_status ?? 'N/A',
                        'tra_error' => $booking->tra_error ?? 'N/A',
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("TRA Fiscalization Failed (ClickPesa Payment): " . $e->getMessage(), [
                    'booking_id' => $booking->id,
                    'booking_code' => $booking->booking_code,
                    'transaction_token' => $transToken,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
            // -----------------------

            Log::info('ClickPesa Payment processed successfully', [
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

            $url = new RedirectController();
            return $url->_redirect($booking->id);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update records in ClickPesa payment', [
                'error' => $e->getMessage(),
                'booking_id' => $booking->id,
                'transaction_token' => $transToken
            ]);

            $url = new RedirectController();
            return $url->_redirect($booking->id);
        }
    }
}
