<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;

/**
 * Africa's Talking bulk SMS gateway.
 *
 * https://developers.africastalking.com/docs/sms/sending/bulk
 *
 * Recipient statusCodes: 100 Processed, 101 Sent, 102 Queued are accepted as
 * success; everything else (401 RiskHold, 402 InvalidSenderId,
 * 403 InvalidPhoneNumber, 405 InsufficientBalance, …) is a failure.
 */
class AfricasTalkingDriver implements SmsDriver
{
    public const LIVE_ENDPOINT = 'https://api.africastalking.com/version1/messaging';
    public const SANDBOX_ENDPOINT = 'https://api.sandbox.africastalking.com/version1/messaging';

    private const SUCCESS_CODES = [100, 101, 102];

    private const STATUS_MESSAGES = [
        401 => 'Risk hold',
        402 => 'Invalid sender id',
        403 => 'Invalid phone number',
        404 => 'Unsupported number type',
        405 => 'Insufficient balance',
        406 => 'User in blacklist',
        407 => 'Could not route the message',
        409 => 'Do not disturb rejection',
        500 => 'Internal server error',
        501 => 'Gateway error',
        502 => 'Rejected by gateway',
    ];

    public function __construct(
        private string $username,
        private string $apiKey,
        private ?string $senderId = null,
        private bool $sandbox = true,
    ) {
    }

    public function name(): string
    {
        return 'africastalking';
    }

    public function endpoint(): string
    {
        return $this->sandbox ? self::SANDBOX_ENDPOINT : self::LIVE_ENDPOINT;
    }

    public function send(string $destination, string $message): SmsResult
    {
        if ($this->username === '' || $this->apiKey === '') {
            return SmsResult::fail("Africa's Talking credentials are not configured");
        }

        $payload = [
            'username' => $this->username,
            'to' => $destination,
            'message' => $message,
        ];

        // Sandbox rejects unregistered sender ids, so only send `from` when the
        // shortcode has actually been approved for the live account.
        if (!$this->sandbox && !empty($this->senderId)) {
            $payload['from'] = $this->senderId;
        }

        try {
            $response = Http::asForm()
                ->withHeaders([
                    'apiKey' => $this->apiKey,
                    'Accept' => 'application/json',
                ])
                ->timeout(20)
                ->connectTimeout(10)
                // Deliberately no retry: a resend after a lost response would
                // bill and deliver the message twice.
                ->post($this->endpoint(), $payload);
        } catch (\Throwable $e) {
            return SmsResult::fail('Request failed: ' . $e->getMessage());
        }

        if (!$response->successful()) {
            // AT returns plain text on auth/validation errors.
            $body = trim($response->body());
            $hint = '';
            if ($response->status() === 401) {
                $hint = ' Check Username matches the AT dashboard (not the app display name),'
                    . ' re-paste the API key and Save, and keep Sandbox off for live keys.';
            }

            return SmsResult::fail(
                'HTTP ' . $response->status()
                . ($body !== '' ? ': ' . mb_substr($body, 0, 180) : '')
                . $hint
            );
        }

        $recipient = $response->json('SMSMessageData.Recipients.0');

        if (!is_array($recipient)) {
            $summary = (string) $response->json('SMSMessageData.Message', 'No recipient in response');

            return SmsResult::fail($summary);
        }

        $code = (int) ($recipient['statusCode'] ?? 0);
        [$cost, $currency] = $this->parseCost($recipient['cost'] ?? null);

        if (in_array($code, self::SUCCESS_CODES, true)) {
            return SmsResult::ok(
                $recipient['messageId'] ?? null,
                $recipient['status'] ?? 'Sent',
                $cost,
                $currency,
            );
        }

        $reason = $recipient['status'] ?? (self::STATUS_MESSAGES[$code] ?? 'Unknown error');

        return SmsResult::fail($reason . ' (code ' . $code . ')', (string) $reason);
    }

    /**
     * "TZS 20.0000" -> [20.0, 'TZS']; "0" -> [0.0, null].
     *
     * @return array{0: float|null, 1: string|null}
     */
    private function parseCost($raw): array
    {
        if (!is_string($raw) || trim($raw) === '') {
            return [null, null];
        }

        if (preg_match('/^([A-Za-z]{3})\s*([0-9.]+)$/', trim($raw), $m)) {
            return [(float) $m[2], strtoupper($m[1])];
        }

        return [is_numeric($raw) ? (float) $raw : null, null];
    }
}
