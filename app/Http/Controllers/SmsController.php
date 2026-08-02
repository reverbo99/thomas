<?php

namespace App\Http\Controllers;

use App\Services\Sms\SmsManager;
use App\Services\Sms\SmsResult;

class SmsController extends Controller
{
    public function __construct(private ?SmsManager $manager = null)
    {
        $this->manager = $manager ?: app(SmsManager::class);
    }

    /**
     * Send SMS through the configured gateway. Handles network/API errors
     * without throwing.
     *
     * @param string|null $destination Phone number in any local or international shape
     * @param string $message Message text
     * @return string|false Message ID on success, false on failure
     */
    public function sms_send($destination, $message)
    {
        return $this->manager->sendLegacy($destination, (string) $message);
    }

    /**
     * Same send, but with the full gateway outcome (status, cost, error) for
     * callers that need to report it — e.g. the admin test-send form.
     */
    public function send($destination, string $message, ?string $driver = null): SmsResult
    {
        return $this->manager->send($destination, $message, $driver);
    }
}
