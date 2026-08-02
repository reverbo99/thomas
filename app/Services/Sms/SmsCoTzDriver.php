<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;

/**
 * Legacy sms.co.tz HTTP gateway.
 *
 * Responds with a comma separated string: "OK,<detail>,<messageId>" on success,
 * anything else on failure.
 */
class SmsCoTzDriver implements SmsDriver
{
    public const ENDPOINT = 'https://www.sms.co.tz/api.php';

    public function __construct(
        private string $username,
        private string $password,
        private string $senderId,
    ) {
    }

    public function name(): string
    {
        return 'smscotz';
    }

    public function send(string $destination, string $message): SmsResult
    {
        if ($this->username === '' || $this->password === '') {
            return SmsResult::fail('sms.co.tz credentials are not configured');
        }

        try {
            $response = Http::timeout(20)
                ->connectTimeout(10)
                // No retry — a resend after a lost response double-delivers.
                ->get(self::ENDPOINT, [
                    'do' => 'sms',
                    'username' => $this->username,
                    'password' => $this->password,
                    'senderid' => $this->senderId,
                    'dest' => $destination,
                    'msg' => $message,
                ]);
        } catch (\Throwable $e) {
            return SmsResult::fail('Request failed: ' . $e->getMessage());
        }

        if (!$response->successful()) {
            return SmsResult::fail('HTTP ' . $response->status());
        }

        $parts = explode(',', trim($response->body()));
        $status = $parts[0] ?? '';

        if ($status === 'OK') {
            return SmsResult::ok($parts[2] ?? null, 'sent');
        }

        return SmsResult::fail($parts[1] ?? ('Gateway returned: ' . $status));
    }
}
