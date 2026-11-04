<?php

/*
|--------------------------------------------------------------------------
| ClickPesa API configuration
|--------------------------------------------------------------------------
|
| All ClickPesa settings live here so they survive `php artisan config:cache`.
| Calling env() directly from controllers returns null once the config is
| cached, which used to make payment requests fail with
| "Failed to obtain access token from ClickPesa" even when the credentials
| were present in .env.
|
*/

$baseUrl = rtrim((string) env('CLICKPESA_API_BASE_URL', 'https://api.clickpesa.com/third-parties'), '/');

return [

    'base_url' => $baseUrl,

    'api_key' => env('CLICKPESA_API_KEY'),
    'client_id' => env('CLICKPESA_CLIENT_ID'),
    'checksum_key' => env('CLICKPESA_CHECKSUM_KEY'),

    // Token + USSD push endpoints.
    'token_url' => env('CLICKPESA_TOKEN_URL', $baseUrl . '/generate-token'),
    'endpoint' => env('CLICKPESA_ENDPOINT', $baseUrl . '/payments/preview-ussd-push-request'),

    // HTTP behaviour.
    'timeout' => (int) env('CLICKPESA_TIMEOUT', 45),
    'connect_timeout' => (int) env('CLICKPESA_CONNECT_TIMEOUT', 15),
    'token_retries' => (int) env('CLICKPESA_TOKEN_RETRIES', 3),

    // Amount limits for mobile-money/USSD pushes (TZS).
    'min_amount_tzs' => (float) env('CLICKPESA_MIN_AMOUNT_TZS', 908),
    'max_amount_tzs' => (float) env('CLICKPESA_MAX_AMOUNT_TZS', 3000000),

    'currency' => env('CLICKPESA_PAYOUT_CURRENCY', 'TZS'),

];
