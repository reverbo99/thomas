<?php

namespace App\Services\Sms;

/**
 * Outcome of a single send attempt, normalised across gateways.
 */
class SmsResult
{
    public function __construct(
        public bool $success,
        public ?string $messageId = null,
        public ?string $status = null,
        public ?string $error = null,
        public ?float $cost = null,
        public ?string $currency = null,
    ) {
    }

    public static function ok(?string $messageId, ?string $status = null, ?float $cost = null, ?string $currency = null): self
    {
        return new self(true, $messageId, $status ?? 'sent', null, $cost, $currency);
    }

    public static function fail(string $error, ?string $status = null): self
    {
        return new self(false, null, $status ?? 'failed', $error);
    }
}
