<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * FastHub Bulk SMS Gateway driver.
 *
 * Documented request (POST, application/json):
 *
 *   {
 *     "auth": { "client_id": "...", "client_secret": "..." },
 *     "messages": [
 *       { "text": "...", "msisdn": "...", "source": "...", "reference": "..." }
 *     ]
 *   }
 *
 * Documented response: { "status": bool, "message": string, "data": object }
 *
 * `reference` must be at least 20 characters and is echoed back in the
 * delivery-status callback, so we generate a UUID per message and keep it as
 * the sms_logs.message_id used to match the callback.
 */
class FastHubDriver implements SmsDriver
{
    public function __construct(
        private string $clientId,
        private string $clientSecret,
        private string $senderId,
        private string $endpoint,
        private ?int $gatewayId = null,
    ) {
    }

    public function name(): string
    {
        return 'fasthub';
    }

    public function send(string $destination, string $message): SmsResult
    {
        if ($this->gatewayId === null) {
            return SmsResult::fail('FastHub gateway is not configured. Add a configuration in Settings > SMS Gateway and select it as default.');
        }

        if ($this->clientId === '' || $this->clientSecret === '') {
            return SmsResult::fail('FastHub Client ID / Client secret are missing for the selected configuration.');
        }

        // The endpoint comes from the saved configuration; when that is blank
        // the system-wide default from config/services.php is used.
        $endpoint = $this->endpoint !== ''
            ? $this->endpoint
            : (string) config('services.fasthub.endpoint', '');

        if ($endpoint === '') {
            return SmsResult::fail('FastHub API endpoint is not configured. Add the endpoint URL to the gateway configuration.');
        }

        // FastHub expects the full international MSISDN — always send the
        // country code (255 for Tanzania), never a local "07..." number.
        $msisdn = $this->internationalMsisdn($destination);

        if ($msisdn === null) {
            return SmsResult::fail('Invalid destination number: ' . $destination);
        }

        // The documented minimum is 20 characters.
        $reference = (string) Str::uuid();

        try {
            $response = Http::timeout((int) config('services.fasthub.timeout', 20))
                ->connectTimeout(10)
                // No retry — a resend after a lost response double-delivers.
                ->acceptJson()
                ->asJson()
                ->post($endpoint, [
                    'auth' => [
                        'client_id' => $this->clientId,
                        'client_secret' => $this->clientSecret,
                    ],
                    'messages' => [[
                        'text' => $message,
                        'msisdn' => $msisdn,
                        'source' => $this->senderId,
                        'reference' => $reference,
                    ]],
                ]);
        } catch (\Throwable $e) {
            return SmsResult::fail('Request failed: ' . $e->getMessage());
        }

        $body = $response->json();
        $error = is_array($body) && is_string($body['message'] ?? null)
            ? mb_substr(trim($body['message']), 0, 190)
            : '';

        if (!$response->successful()) {
            // FastHub returns 400 with a useful message ("Invalid credentials",
            // "Insufficient balance", …) — surface that instead of the code.
            return SmsResult::fail($error !== '' ? $error : 'HTTP ' . $response->status());
        }

        if (!is_array($body)) {
            return SmsResult::fail('FastHub returned a non-JSON response.');
        }

        if ($this->isSuccessfulStatus($body['status'] ?? null)) {
            return SmsResult::ok($reference, 'sent');
        }

        return SmsResult::fail($error !== '' ? $error : 'FastHub rejected the message.');
    }

    /**
     * Normalise the destination to bare international digits starting with the
     * country code (e.g. "0712345678" -> "255712345678"), which is the format
     * FastHub requires. Null when the value contains no usable digits.
     */
    private function internationalMsisdn(string $destination): ?string
    {
        $countryCode = PhoneNumber::defaultCountryCode();
        $digits = PhoneNumber::digits($destination, $countryCode);

        if ($digits === null) {
            // Fall back to the raw digits so the gateway can still report its
            // own validation error instead of us dropping the send silently.
            $digits = preg_replace('/\D/', '', $destination) ?? '';

            if ($digits === '') {
                return null;
            }
        }

        if ($countryCode !== '' && !str_starts_with($digits, $countryCode)) {
            $digits = $countryCode . ltrim($digits, '0');
        }

        return $digits;
    }

    /**
     * The gateway documents a boolean, but its health check (and some gateway
     * versions) answer with strings like "ok", so accept those too.
     */
    private function isSuccessfulStatus($status): bool
    {
        if ($status === true || $status === 1) {
            return true;
        }

        return is_string($status)
            && in_array(strtolower(trim($status)), ['1', 'true', 'ok', 'success', 'sent'], true);
    }
}
