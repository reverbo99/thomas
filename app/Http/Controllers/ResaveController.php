<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\PDOController;
use App\Http\Controllers\TigosecureController;
use App\Http\Controllers\ClickPesaController;
use App\Http\Controllers\CashController;

class ResaveController extends Controller
{
    /**
     * Load a resaved booking the current payer is allowed to pay.
     * Customers and vendors use auth ownership; guests use booking-info lookup session.
     */
    private function findResavedBookingForPayer(int $bookingId): ?Booking
    {
        $booking = Booking::where('id', $bookingId)
            ->where('payment_status', 'resaved')
            ->first();

        if (!$booking) {
            return null;
        }

        if (auth()->check()) {
            $user = auth()->user();

            if ($user->role === 'customer') {
                if ((int) ($booking->user_id ?? 0) === (int) $user->id) {
                    return $booking;
                }

                // Orphan reserved tickets (pre-user_id fix / phone-variant mismatch): claim then allow pay.
                if ((int) ($booking->user_id ?? 0) === 0
                    && (empty($booking->vender_id) || (string) $booking->vender_id === '0')
                ) {
                    $email = trim((string) ($user->email ?? ''));
                    $phone = $user->contact ?? $user->phone ?? null;
                    $emailMatch = $email !== ''
                        && strcasecmp((string) ($booking->customer_email ?? ''), $email) === 0;
                    $phoneMatch = false;
                    if (!empty($phone)) {
                        $variants = [];
                        $normalized = normalize_tanzania_phone_for_booking((string) $phone);
                        if ($normalized !== '') {
                            $variants[] = $normalized;
                            $canonical = normalize_tanzania_phone_to_canonical($normalized);
                            if ($canonical !== null) {
                                $variants = array_merge($variants, tanzania_phone_booking_lookup_variants($canonical));
                            }
                        }
                        $phoneMatch = in_array((string) ($booking->customer_phone ?? ''), array_map('strval', $variants), true);
                    }

                    if ($emailMatch || $phoneMatch) {
                        $booking->user_id = $user->id;
                        $booking->save();

                        return $booking;
                    }
                }

                return null;
            }

            if ($user->role === 'vender') {
                if ((string) $booking->vender_id !== (string) $user->id) {
                    return null;
                }

                return $booking;
            }

            return null;
        }

        $allowedIds = session('guest_booking_lookup_ids', []);
        if (!is_array($allowedIds)) {
            return null;
        }

        return in_array($bookingId, array_map('intval', $allowedIds), true) ? $booking : null;
    }

    private function resavedTicketsListRoute(): string
    {
        if (auth()->check() && auth()->user()->role === 'vender') {
            return route('vender.resaved.tickets');
        }

        if (auth()->check() && auth()->user()->role === 'customer') {
            return route('customer.mybooking');
        }

        return route('info');
    }

    private function payResavedRoute(int $bookingId): string
    {
        if (auth()->check() && auth()->user()->role === 'vender') {
            return route('vender.pay.resaved', ['id' => $bookingId]);
        }

        if (auth()->check() && auth()->user()->role === 'customer') {
            return route('customer.pay.resaved', ['id' => $bookingId]);
        }

        return route('guest.pay.resaved', ['id' => $bookingId]);
    }

    /**
     * Complete a resaved-ticket payment when admin test mode is enabled (no real gateway).
     */
    public function testPayResaved(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|integer|exists:bookings,id',
        ]);

        $settings = Setting::first();
        if (!($settings->test_mode ?? false)) {
            return redirect()->back()->with('error', 'Test mode is not enabled.');
        }

        $booking = $this->findResavedBookingForPayer((int) $request->booking_id);

        if (!$booking) {
            return redirect()->to($this->resavedTicketsListRoute())
                ->withErrors(['payment_error' => 'Booking not found or not eligible for payment.']);
        }

        Session::put('booking', $booking);

        return app(TestPaymentController::class)->processPayment($request);
    }

    /**
     * Redirect back to pay-resaved page with error message.
     */
    private function backWithError($bookingId, $message, $key = 'payment_error')
    {
        return redirect()->to($this->payResavedRoute($bookingId))
            ->withErrors([$key => $message])
            ->withInput();
    }

    /**
     * Normalize phone for ClickPesa/DPO/Mix: digits only, 255XXXXXXXXX (Tanzania).
     * Returns normalized string or null if invalid/empty.
     */
    private function normalizePaymentPhone(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return normalize_tanzania_phone_to_canonical($value);
    }

    /**
     * Resolve payment phone from request or booking; normalize and validate.
     * Returns [normalized_phone, error_message]. error_message is null on success.
     */
    private function resolvePaymentPhone(Request $request, Booking $booking, string $formField = 'payment_phone', string $mixField = 'payment_contact'): array
    {
        $raw = $request->input($formField) ?: $request->input($mixField);
        $raw = $raw ? trim($raw) : '';

        if ($raw !== '') {
            $normalized = $this->normalizePaymentPhone($raw);
            if ($normalized !== null) {
                return [$normalized, null];
            }
        }

        $fallback = $booking->customer_phone ?? $booking->phone ?? null;
        $normalized = $this->normalizePaymentPhone($fallback);
        if ($normalized !== null) {
            return [$normalized, null];
        }

        return [null, __('customer/busroot.invalid_payment_phone')];
    }

    public function byMix(Request $request)
    {
        $request->validate(['booking_id' => 'required']);

        $booking = $this->findResavedBookingForPayer((int) $request->booking_id);

        if (!$booking) {
            return redirect()->to($this->resavedTicketsListRoute())
                ->withErrors(['payment_error' => 'Booking not found or not eligible for payment.']);
        }

        [$phone, $phoneError] = $this->resolvePaymentPhone($request, $booking, 'payment_phone', 'payment_contact');
        if ($phoneError !== null) {
            return $this->backWithError($booking->id, $phoneError);
        }

        $postedData = [
            'account' => $phone,
            'countryCode' => '255',
            'country' => 'TZA',
            'firstName' => $booking->customer_name ?? $booking->first_name ?? 'Customer',
            'lastName' => $booking->last_name ?? '',
            'email' => $booking->customer_email ?? $booking->email ?? '',
            'amount' => $booking->amount,
            'currency' => 'TZS',
        ];

        try {
            $tigoSecureController = new TigosecureController();
            return $tigoSecureController->payment($postedData);
        } catch (\Exception $e) {
            Log::error('Resaved Mix payment failed: ' . $e->getMessage(), [
                'booking_id' => $booking->id,
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->backWithError($booking->id, 'Mix payment failed: ' . $e->getMessage());
        }
    }

    public function byPdo(Request $request)
    {
        $request->validate(['booking_id' => 'required']);

        $booking = $this->findResavedBookingForPayer((int) $request->booking_id);

        if (!$booking) {
            return redirect()->to($this->resavedTicketsListRoute())
                ->withErrors(['payment_error' => 'Booking not found or not eligible for payment.']);
        }

        [$phone, $phoneError] = $this->resolvePaymentPhone($request, $booking, 'payment_phone');
        if ($phoneError !== null) {
            return $this->backWithError($booking->id, $phoneError);
        }

        try {
            $pdoController = new PDOController();
            return $pdoController->initiatePayment(
                $booking->amount,
                $booking->first_name ?? $booking->customer_name ?? 'Customer',
                $booking->last_name ?? '',
                $phone,
                $booking->email ?? $booking->customer_email ?? '',
                $booking->booking_code
            );
        } catch (\Exception $e) {
            Log::error('Resaved DPO payment failed: ' . $e->getMessage(), [
                'booking_id' => $booking->id,
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->backWithError($booking->id, 'DPO payment failed: ' . $e->getMessage());
        }
    }

    /**
     * Pay resaved ticket via ClickPesa.
     */
    public function byClickPesa(Request $request)
    {
        $request->validate(['booking_id' => 'required|exists:bookings,id']);

        $booking = $this->findResavedBookingForPayer((int) $request->booking_id);

        if (!$booking) {
            return redirect()->to($this->resavedTicketsListRoute())
                ->withErrors(['payment_error' => 'Booking not found or not eligible for payment.']);
        }

        $setting = Setting::first();
        $price = $booking->amount;
        $fees = $setting ? ($setting->service + ($setting->service_percentage / 100 * ($booking->amount * 100 / 118))) : 0;
        $total = round($price + $fees);

        $name = $booking->customer_name ?? 'Customer';
        $email = $booking->customer_email ?? '';

        [$phone, $phoneError] = $this->resolvePaymentPhone($request, $booking, 'payment_phone');
        if ($phoneError !== null) {
            return $this->backWithError($booking->id, $phoneError);
        }

        Session::put('booking', $booking);

        $orderReference = $booking->booking_code . '-' . time();

        try {
            $clickpesa = new ClickPesaController();
            return $clickpesa->initiatePayment(
                $total,
                $name,
                $name,
                $phone,
                $email,
                $orderReference
            );
        } catch (\Throwable $e) {
            Log::error('Resaved ClickPesa payment failed: ' . $e->getMessage(), [
                'booking_id' => $booking->id,
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return $this->backWithError(
                $booking->id,
                'ClickPesa error: ' . (strlen($e->getMessage()) > 200 ? substr($e->getMessage(), 0, 200) . '…' : $e->getMessage())
            );
        }
    }

    /**
     * Pay resaved ticket via cash (vendor portal only).
     */
    public function byCash(Request $request)
    {
        $request->validate(['booking_id' => 'required|integer|exists:bookings,id']);

        if (!auth()->check() || auth()->user()->role !== 'vender') {
            abort(403);
        }

        $booking = $this->findResavedBookingForPayer((int) $request->booking_id);

        if (!$booking) {
            return redirect()->route('vender.resaved.tickets')
                ->withErrors(['payment_error' => 'Booking not found or not eligible for payment.']);
        }

        $xcode = 'CASH-' . strtoupper(uniqid() . rand(100, 999));

        try {
            Session::put('booking', $booking);
            $cash = new CashController();

            return $cash->cash($booking, $xcode);
        } catch (\Exception $e) {
            Log::error('Resaved cash payment failed: ' . $e->getMessage(), [
                'booking_id' => $booking->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->backWithError($booking->id, 'Cash payment failed: ' . $e->getMessage());
        }
    }
}
