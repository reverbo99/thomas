<?php

namespace App\Http\Controllers;

use App\Models\AdminWallet;
use App\Models\Bima;
use App\Models\Booking;
use App\Models\bus;
use App\Models\Campany;
use App\Models\PaymentFees;
use App\Models\Roundtrip;
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

class PDOController extends Controller
{
    // DPO API Configuration
    private $companyToken;
    private $serviceType;
    private $endpoint;
    private $paymentUrl;

    public function __construct()
    {
        $this->companyToken = "C40E4138-3DF7-4A56-A6D1-375A49407A1C"; // Test Company Token
        $this->serviceType = "54842"; // Test Product Service Type
        $this->endpoint = "https://secure.3gdirectpay.com/API/v6/";
        $this->paymentUrl = "https://secure.3gdirectpay.com/payv3.php?ID=";
    }

    /**
     * Initiate DPO payment
     */
    public function initiatePayment($amount, $first_name, $last_name, $phone, $email, $order_id = null)
    {
        // Prepare order details
        $orderDetails = [
            'amount' => $amount,
            'order_id' => $order_id ?? 'ORD-' . now(),
            'first_name' => $first_name,
            'last_name' => $last_name,
            'phone' => $phone,
            'email' => $email,
            'redirect_url' => route('dpo.callback'),
            'back_url' => route('dpo.cancel'),
        ];

        // Create transaction token
        $tokenResponse = $this->createDPOToken($orderDetails);

        // Check if tokenResponse is a string (error) or object (success)
        if (is_string($tokenResponse)) {
            // Handle error case
            Log::error('DPO Token Creation Failed', [
                'order_id' => $orderDetails['order_id'],
                'error' => $tokenResponse,
                'response' => $tokenResponse
            ]);

            return back()->withErrors(['dpo_error' => $tokenResponse]);
        }

        // Check if we have a valid response object with TransToken
        if ($tokenResponse && isset($tokenResponse->TransToken)) {
            $transToken = (string)$tokenResponse->TransToken;
            $paymentLink = $this->paymentUrl . $transToken;

            // Log successful token creation
            Log::info('DPO Token Created Successfully', [
                'order_id' => $orderDetails['order_id'],
                'transaction_token' => $transToken,
                'amount' => $orderDetails['amount']
            ]);

            // Redirect to payment page
            return redirect()->away($paymentLink);
        } else {
            $errorMessage = isset($tokenResponse->ResultExplanation)
                ? (string)$tokenResponse->ResultExplanation
                : "Unknown error creating token";

            Log::error('DPO Token Creation Failed', [
                'order_id' => $orderDetails['order_id'],
                'error' => $errorMessage,
                'response' => $tokenResponse
            ]);

            return back()->withErrors(['dpo_error' => $errorMessage]);
        }
    }

    public function VenderinitiatePayment($amount, $first_name, $last_name, $phone, $email, $order_id = null)
    {
        // Prepare order details
        $orderDetails = [
            'amount' => $amount,
            'order_id' => $order_id ?? 'ORD-' . now(),
            'first_name' => $first_name,
            'last_name' => $last_name,
            'phone' => $phone,
            'email' => $email,
            'redirect_url' => route('dpo.callback'),
            'back_url' => route('dpo.cancel'),
        ];

        // Create transaction token
        $tokenResponse = $this->createDPOToken($orderDetails);

        // Check if tokenResponse is a string (error) or object (success)
        if (is_string($tokenResponse)) {
            // Handle error case
            Log::error('DPO Token Creation Failed', [
                'order_id' => $orderDetails['order_id'],
                'error' => $tokenResponse,
                'response' => $tokenResponse
            ]);

            return back()->withErrors(['dpo_error' => $tokenResponse]);
        }

        // Check if we have a valid response object with TransToken
        if ($tokenResponse && isset($tokenResponse->TransToken)) {
            $transToken = (string)$tokenResponse->TransToken;
            $paymentLink = $this->paymentUrl . $transToken;

            // Log successful token creation
            Log::info('DPO Token Created Successfully', [
                'order_id' => $orderDetails['order_id'],
                'transaction_token' => $transToken,
                'amount' => $orderDetails['amount']
            ]);

            Session::put('vender', 'vender');

            // Redirect to payment page
            return redirect()->away($paymentLink);
        } else {
            $errorMessage = isset($tokenResponse->ResultExplanation)
                ? (string)$tokenResponse->ResultExplanation
                : "Unknown error creating token";

            Log::error('DPO Token Creation Failed', [
                'order_id' => $orderDetails['order_id'],
                'error' => $errorMessage,
                'response' => $tokenResponse
            ]);

            return back()->withErrors(['dpo_error' => $errorMessage]);
        }
    }


    /**
     * Handle DPO callback (success and failure)
     */
    public function handleCallback(Request $request)
    {
        $transToken = $request->get('TransactionToken');
        $resultCode = $request->get('Result');

        // Handle cancellation (Result=904)
        if ($resultCode === '904') {
            Log::info('DPO Transaction Canceled by User', [
                'transaction_token' => $transToken,
                'result_code' => $resultCode,
                'query_params' => $request->all()
            ]);

            // TODO: Update your database - mark order as canceled
            return view('dpo.cancel', [
                'transactionToken' => $transToken,
                'resultCode' => $resultCode,
                'message' => 'Transaction was canceled by the user'
            ]);
        }

        // Verify transaction if transToken is present
        if ($transToken) {
            $verifyResponse = $this->verifyDPOToken($transToken);

            if ($verifyResponse && (string)$verifyResponse->Result == '000') {
                Log::info('DPO Payment Verification Successful', [
                    'transaction_token' => $transToken,
                    'response' => $verifyResponse
                ]);

                $vender = Session::get('vender') ?? '';

                if (!$vender) {
                    return $this->processSuccessfulPayment($transToken, $request->CompanyRef, $verifyResponse);
                } else {
                    Session::forget('vender');
                    $venderclass = new VenderWalletController();
                    return $venderclass->returned();
                }

            } else {
                $errorMessage = isset($verifyResponse->ResultExplanation)
                    ? (string)$verifyResponse->ResultExplanation
                    : (is_string($verifyResponse) ? $verifyResponse : "Unknown verification error");

                Log::error('DPO Payment Verification Failed', [
                    'transaction_token' => $transToken,
                    'error' => $errorMessage,
                    'response' => $verifyResponse
                ]);

                // Check for cancellation in verifyToken response
                if (isset($verifyResponse->Result) && (string)$verifyResponse->Result == '904') {
                    Log::info('DPO Transaction Canceled (from verification)', [
                        'transaction_token' => $transToken
                    ]);

                    // TODO: Update your database - mark order as canceled
                    return view('dpo.cancel', [
                        'transactionToken' => $transToken,
                        'resultCode' => '904',
                        'message' => 'Transaction was canceled by the user'
                    ]);
                }

                return  [
                    'transactionToken' => $transToken,
                    'errorMessage' => $errorMessage,
                    'response' => $verifyResponse
                ];
            }
        } else {
            Log::warning('No Transaction Token in DPO Callback', [
                'query_params' => $request->all()
            ]);

            return [
                'errorMessage' => 'No transaction token provided in callback',
                'queryParams' => $request->all()
            ];
        }
    }

    /**
     * Handle cancellation specifically
     */
    public function handleCancel(Request $request)
    {
        $transToken = $request->get('transToken');
        $resultCode = $request->get('Result');

        Log::info('DPO Transaction Canceled (Direct)', [
            'transaction_token' => $transToken,
            'result_code' => $resultCode,
            'query_params' => $request->all()
        ]);

        // TODO: Update your database - mark order as canceled

        return view('dpo.cancel', [
            'transactionToken' => $transToken,
            'resultCode' => $resultCode,
            'message' => 'Transaction was canceled'
        ]);
    }

    /**
     * Create DPO transaction token
     */
    private function createDPOToken($orderDetails)
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>
                <API3G>
                    <CompanyToken>' . $this->companyToken . '</CompanyToken>
                    <Request>createToken</Request>
                    <Transaction>
                        <PaymentAmount>' . $orderDetails['amount'] . '</PaymentAmount>
                        <PaymentCurrency>TZS</PaymentCurrency>
                        <CompanyRef>' . $orderDetails['order_id'] . '</CompanyRef>
                        <CustomerFirstName>' . $orderDetails['first_name'] . '</CustomerFirstName>
                        <CustomerLastName>' . $orderDetails['last_name'] . '</CustomerLastName>
                        <CustomerPhone>' . $orderDetails['phone'] . '</CustomerPhone>
                        <CustomerEmail>' . $orderDetails['email'] . '</CustomerEmail>
                        <RedirectURL>' . $orderDetails['redirect_url'] . '</RedirectURL>
                        <BackURL>' . $orderDetails['back_url'] . '</BackURL>
                        <TransactionSource>web</TransactionSource>
                    </Transaction>
                    <Services>
                        <Service>
                            <ServiceType>' . $this->serviceType . '</ServiceType>
                            <ServiceDescription>Product Payment</ServiceDescription>
                            <ServiceDate>' . date('Y/m/d') . '</ServiceDate>
                        </Service>
                    </Services>
                </API3G>';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->endpoint);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/xml'));
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode != 200) {
            Log::error('DPO Create Token HTTP Error', [
                'http_code' => $httpCode,
                'order_id' => $orderDetails['order_id']
            ]);
            return "HTTP Error: $httpCode - Failed to connect to DPO API";
        }

        $xmlResponse = simplexml_load_string($response);
        if ($xmlResponse === false) {
            Log::error('DPO Create Token XML Parse Error', [
                'response' => $response,
                'order_id' => $orderDetails['order_id']
            ]);
            return "Error parsing XML response: $response";
        }

        Log::debug('DPO Create Token Response', [
            'order_id' => $orderDetails['order_id'],
            'response' => $xmlResponse
        ]);

        return $xmlResponse;
    }

    /**
     * Verify DPO transaction token
     */
    private function verifyDPOToken($transToken)
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>
                <API3G>
                    <CompanyToken>' . $this->companyToken . '</CompanyToken>
                    <Request>verifyToken</Request>
                    <TransactionToken>' . $transToken . '</TransactionToken>
                </API3G>';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->endpoint);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/xml'));
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode != 200) {
            Log::error('DPO Verify Token HTTP Error', [
                'http_code' => $httpCode,
                'transaction_token' => $transToken
            ]);
            return "HTTP Error: $httpCode - Failed to connect to DPO API";
        }

        $xmlResponse = simplexml_load_string($response);
        if ($xmlResponse === false) {
            Log::error('DPO Verify Token XML Parse Error', [
                'response' => $response,
                'transaction_token' => $transToken
            ]);
            return "Error parsing XML response: $response";
        }

        Log::debug('DPO Verify Token Response', [
            'transaction_token' => $transToken,
            'response' => $xmlResponse
        ]);

        return $xmlResponse;
    }

    private function processSuccessfulPayment($transToken, $companyRef, $verifyResponse)
    {
        $booking1 = session()->get('booking1');
        $booking2 = session()->get('booking2');

        if (!is_null($booking1) && !is_null($booking2)) {
            $round = new RoundpaymentController();
            $code1 = $booking1->booking_code ?? 'N/A';
            $code2 = $booking2->booking_code ?? 'N/A';

            try {
                $data1 = $round->roundtrip($transToken, $transToken, $verifyResponse, $code1, 'dpo');
                $data2 = $round->roundtrip($transToken, $transToken, $verifyResponse, $code2, 'dpo');

                if (is_array($data1) && isset($data1['errorMessage'])) {
                    session()->forget(['booking1', 'booking2', 'is_round', 'booking_form']);
                    return view('dpo.error', [
                        'message' => $data1['errorMessage'] ?? 'Booking not found',
                        'transactionToken' => $transToken
                    ]);
                }
                if (is_array($data2) && isset($data2['errorMessage'])) {
                    session()->forget(['booking1', 'booking2', 'is_round', 'booking_form']);
                    return view('dpo.error', [
                        'message' => $data2['errorMessage'] ?? 'Booking not found',
                        'transactionToken' => $transToken
                    ]);
                }

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

                session()->forget(['booking1', 'booking2', 'is_round', 'booking_form']);

                return view('dpo.error', [
                    'message' => 'Failed to process round trip payment: ' . $e->getMessage(),
                    'transactionToken' => $transToken
                ]);
            }
        }

        // Session lost: settle every unpaid leg sharing this DPO transaction token.
        $roundByRef = (new RoundpaymentController())->settleAllByTransactionRef($transToken, $verifyResponse, 'dpo');
        if (is_array($roundByRef) && isset($roundByRef['errorMessage'])) {
            return view('dpo.error', [
                'message' => $roundByRef['errorMessage'] ?? 'Booking not found',
                'transactionToken' => $transToken,
            ]);
        }
        if (is_array($roundByRef) && count($roundByRef) >= 2) {
            session()->forget(['booking1', 'booking2', 'is_round', 'booking_form']);
            $red = new RedirectController();
            return $red->showRoundTripBookingStatus($roundByRef[0], $roundByRef[1]);
        }

        $code = session('booking')->booking_code;
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
            Log::info('DPO: Booking already paid', ['booking_code' => $booking->booking_code]);
            Session::forget('booking');
            $url = new RedirectController();
            return $url->_redirect($booking->id);
        }

        DB::beginTransaction();

        try {
            $settlementService = app(BookingSettlementService::class);
            $settled = $settlementService->settlePaidBooking($booking, [
                'trans_status' => 'success',
                'trans_token' => $transToken,
                'payment_method' => 'dpo',
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
                Log::info('TRA Fiscalization Starting (DPO Payment)', [
                    'booking_id' => $booking->id,
                    'booking_code' => $booking->booking_code,
                    'payment_method' => 'dpo',
                    'amount' => $booking->amount,
                    'transaction_token' => $transToken,
                ]);

                $tra = new \App\Services\TraVfdService();
                $fiscalized = $tra->fiscalize($booking->refresh());

                if ($fiscalized) {
                    Log::info('TRA Fiscalization Successful (DPO Payment)', [
                        'booking_id' => $booking->id,
                        'booking_code' => $booking->booking_code,
                        'tra_status' => $booking->tra_status,
                        'tra_vnum' => $booking->tra_vnum ?? 'N/A',
                    ]);
                } else {
                    Log::warning('TRA Fiscalization Returned False (DPO Payment)', [
                        'booking_id' => $booking->id,
                        'booking_code' => $booking->booking_code,
                        'tra_status' => $booking->tra_status ?? 'N/A',
                        'tra_error' => $booking->tra_error ?? 'N/A',
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("TRA Fiscalization Failed (DPO Payment): " . $e->getMessage(), [
                    'booking_id' => $booking->id,
                    'booking_code' => $booking->booking_code,
                    'transaction_token' => $transToken,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
            // -----------------------

            Log::info('DPO Payment processed successfully', [
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
            Log::error('Failed to update records in DPO payment', [
                'error' => $e->getMessage(),
                'booking_id' => $booking->id,
                'transaction_token' => $transToken
            ]);

            $url = new RedirectController();
            return $url->_redirect($booking->id);
        }
    }
} 