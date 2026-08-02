<?php

namespace App\Services\Sms;

interface SmsDriver
{
    /**
     * Short key used in logs and in the settings dropdown.
     */
    public function name(): string;

    /**
     * @param string $destination Normalised phone number (see PhoneNumber)
     */
    public function send(string $destination, string $message): SmsResult;
}
