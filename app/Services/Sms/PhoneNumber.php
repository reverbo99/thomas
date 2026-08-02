<?php

namespace App\Services\Sms;

/**
 * Phone number normalisation shared by every SMS driver.
 *
 * Numbers reach us in a mix of shapes — "0712345678", "255712345678",
 * "+255 712 345 678", "712345678" — and each gateway wants a different one:
 * Africa's Talking requires E.164 with the leading "+", sms.co.tz wants bare
 * digits.
 */
class PhoneNumber
{
    public static function defaultCountryCode(): string
    {
        return (string) config('services.sms.default_country_code', '255');
    }

    /**
     * Bare international digits, e.g. "255712345678". Null when unusable.
     */
    public static function digits($raw, ?string $countryCode = null): ?string
    {
        $countryCode = $countryCode ?: self::defaultCountryCode();
        $value = trim((string) $raw);

        if ($value === '') {
            return null;
        }

        $hadPlus = str_starts_with($value, '+');
        $digits = preg_replace('/\D/', '', $value);

        if ($digits === '') {
            return null;
        }

        if (!$hadPlus) {
            // 00255... international prefix
            if (str_starts_with($digits, '00')) {
                $digits = substr($digits, 2);
            } elseif (str_starts_with($digits, '0')) {
                // Local trunk form: 0712345678 -> 255712345678
                $digits = $countryCode . ltrim($digits, '0');
            } elseif (!str_starts_with($digits, $countryCode) && strlen($digits) <= 9) {
                // Bare subscriber number: 712345678 -> 255712345678
                $digits = $countryCode . $digits;
            }
        }

        // Shortest plausible MSISDN is country code + 8 digits.
        if (strlen($digits) < 10 || strlen($digits) > 15) {
            return null;
        }

        return $digits;
    }

    /**
     * E.164 with the leading "+", e.g. "+255712345678". Null when unusable.
     */
    public static function e164($raw, ?string $countryCode = null): ?string
    {
        $digits = self::digits($raw, $countryCode);

        return $digits === null ? null : '+' . $digits;
    }
}
