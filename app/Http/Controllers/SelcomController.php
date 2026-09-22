<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\BookingSettlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SelcomController extends Controller
{
    /**
     * Public webhook Selcom POSTs after a payment attempt.
     * Register this URL in the Selcom merchant portal:
     *   https://<LIVE_DOMAIN>/selcom/callback
     */
    public function handleCallback(Request $request)
    {
        $payload = $request->all();

        Log::channel('selcom')->info('Selcom callback received', [
            'method' => $request->method(),
            'ip' => $request->ip(),
            'url' => $request->fullUrl(),
            'headers' => [
                'authorization' => $request->header('Authorization'),
                'digest' => $request->header('Digest'),
            ],
            'payload' => $payload,
        ]);

        if (!$this->digestIsValid($request)) {
            Log::channel('selcom')->warning('Selcom callback rejected: invalid digest');

            return response()->json([
                'result' => 'FAIL',
                'resultcode' => '401',
                'message' => 'Invalid digest',
            ], 401);
        }

        $reference = $this->firstFilled($payload, [
            'order_id',
            'transid',
            'reference',
            'transaction_ref_id',
            'external_ref_id',
        ]);
        $status = strtoupper((string) $this->firstFilled($payload, [
            'payment_status',
            'result',
            'trans_status',
            'status',
        ]));

        if ($reference === '') {
            Log::channel('selcom')->warning('Selcom callback missing payment reference', $payload);

            return $this->ack();
        }

        $booking = Booking::query()
            ->where('transaction_ref_id', $reference)
            ->orWhere('external_ref_id', $reference)
            ->orWhere('booking_code', $reference)
            ->orWhere('trans_token', $reference)
            ->first();

        if (!$booking) {
            Log::channel('selcom')->warning('Selcom callback: booking not found', [
                'reference' => $reference,
            ]);

            return $this->ack();
        }

        if ($booking->payment_status === 'Paid') {
            return $this->ack();
        }

        if (!$this->isSuccessfulStatus($status)) {
            $booking->update([
                'payment_status' => 'Failed',
                'trans_status' => $status !== '' ? strtolower($status) : 'failed',
                'transaction_ref_id' => $booking->transaction_ref_id ?: $reference,
                'external_ref_id' => $booking->external_ref_id ?: $this->firstFilled($payload, ['transid', 'reference']),
                'payment_method' => 'selcom',
            ]);

            Log::channel('selcom')->info('Selcom payment marked failed', [
                'booking_id' => $booking->id,
                'status' => $status,
            ]);

            return $this->ack();
        }

        try {
            DB::transaction(function () use ($booking, $payload, $reference, $status) {
                $settled = app(BookingSettlementService::class)->settlePaidBooking($booking, [
                    'trans_status' => 'success',
                    'verification_code' => $this->firstFilled($payload, ['verification_code', 'transid']),
                    'payment_method' => 'selcom',
                    'mfs_id' => $this->firstFilled($payload, ['mfs_id', 'channel']),
                ]);

                $settled['booking']->update([
                    'transaction_ref_id' => $booking->transaction_ref_id ?: $reference,
                    'external_ref_id' => $this->firstFilled($payload, ['transid', 'reference']) ?: $reference,
                    'payment_method' => 'selcom',
                    'trans_status' => strtolower($status) ?: 'success',
                ]);
            });
        } catch (\Throwable $e) {
            Log::channel('selcom')->error('Selcom settlement failed', [
                'booking_id' => $booking->id,
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'result' => 'FAIL',
                'resultcode' => '500',
                'message' => 'Settlement failed',
            ], 500);
        }

        try {
            $tra = new \App\Services\TraVfdService();
            $tra->fiscalize($booking->refresh());
        } catch (\Throwable $e) {
            Log::channel('selcom')->warning('Selcom TRA fiscalization failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }

        Log::channel('selcom')->info('Selcom payment settled', [
            'booking_id' => $booking->id,
            'booking_code' => $booking->booking_code,
            'reference' => $reference,
        ]);

        return $this->ack();
    }

    private function ack()
    {
        return response()->json([
            'result' => 'SUCCESS',
            'resultcode' => '000',
        ]);
    }

    private function isSuccessfulStatus(string $status): bool
    {
        return in_array($status, ['SUCCESS', 'SUCCESSFUL', 'COMPLETED', 'PAID', '000'], true);
    }

    private function firstFilled(array $payload, array $keys): string
    {
        foreach ($keys as $key) {
            $value = trim((string) ($payload[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function digestIsValid(Request $request): bool
    {
        $secret = (string) config('services.selcom.api_secret');
        if ($secret === '') {
            return true;
        }

        $provided = (string) (
            $request->header('Digest')
            ?: $request->header('Authorization')
            ?: $request->input('digest')
        );
        $provided = trim(preg_replace('/^SELCOM\s+/i', '', $provided) ?? '');

        if ($provided === '') {
            return false;
        }

        $payload = $request->all();
        ksort($payload);
        $signed = implode('&', array_map(
            static fn ($key, $value) => $key.'='.(is_scalar($value) ? $value : json_encode($value)),
            array_keys($payload),
            $payload
        ));

        $expected = base64_encode(hash_hmac('sha256', $signed, $secret, true));

        return hash_equals($expected, $provided);
    }
}
