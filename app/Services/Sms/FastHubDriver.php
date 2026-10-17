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

        if ($this->endpoint === '') {
            return SmsResult::fail('FastHub API endpoint is not configured. Add the endpoint URL to the gateway configuration (or set FASTHUB_SMS_ENDPOINT).');
        }

        // The documented minimum is 20 characters.
        $reference = (string) Str::uuid();

        try {
            $response = Http::timeout((int) config('services.fasthub.timeout', 20))
                ->connectTimeout(10)
                // No retry — a resend after a lost response double-delivers.
                ->acceptJson()
                ->asJson()
                ->post($this->endpoint, [
                    'auth' => [
                        'client_id' => $this->clientId,
                        'client_secret' => $this->clientSecret,
                    ],
                    'messages' => [[
                        'text' => $message,
                        'msisdn' => $destination,
                        'source' => $this->senderId,
                        'reference' => $reference,
                    ]],
                ]);
        } catch (\Throwable $e) {
            return SmsResult::fail('Request failed: ' . $e->getMessage());
        }

        if (!$response->successful()) {
            return SmsResult::fail('HTTP ' . $response->status());
        }

        $body = $response->json();

        if (!is_array($body)) {
            return SmsResult::fail('FastHub returned a non-JSON response.');
        }

        if ($this->isSuccessfulStatus($body['status'] ?? null)) {
            return SmsResult::ok($reference, 'sent');
        }

        $error = $body['message'] ?? null;

        return SmsResult::fail(
            is_string($error) && trim($error) !== ''
                ? mb_substr(trim($error), 0, 190)
                : 'FastHub rejected the message.'
        );
    }

    /**
     * The gateway documents a boolean, but tolerate 1/"true" too.
     */
    private function isSuccessfulStatus($status): bool
    {
        return $status === true || $status === 1 || $status === '1' || $status === 'true';
    }
}
