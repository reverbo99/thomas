<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Daily disbursement batches
    |--------------------------------------------------------------------------
    |
    | Every calendar day gets one batch. At the configured run time the batch
    | is "collected" from all outbound sources (booking refunds, excess
    | luggage refunds, vendor/bus-owner payouts, special-hire withdrawals and
    | special-hire customer refunds) and paid out automatically.
    |
    */

    'enabled' => env('DISBURSEMENTS_ENABLED', true),

    // Local application time (config/app.php timezone) the batch starts paying.
    'run_time' => env('DISBURSEMENTS_RUN_TIME', '00:00'),

    // Hard kill-switch. When true the gateway is never called and payouts are
    // simulated/finalised locally. Normally leave this false and use the
    // "Live payouts" switch on the Disbursements page (off = dry run).
    'dry_run' => env('DISBURSEMENTS_DRY_RUN', false),

    // ClickPesa only allows one payout *create* per merchant every 60 seconds.
    // The process command runs every minute and drains one item per run.
    'max_creates_per_run' => (int) env('DISBURSEMENTS_MAX_CREATES_PER_RUN', 1),
    'create_cooldown_seconds' => (int) env('DISBURSEMENTS_CREATE_COOLDOWN_SECONDS', 60),

    // Retry behaviour for transient gateway errors.
    'max_attempts' => (int) env('DISBURSEMENTS_MAX_ATTEMPTS', 5),
    'retry_delay_minutes' => (int) env('DISBURSEMENTS_RETRY_DELAY_MINUTES', 10),

    // How many processing payouts to re-check per process run.
    'status_sync_limit' => (int) env('DISBURSEMENTS_STATUS_SYNC_LIMIT', 25),

    // Older unprocessed batches are picked up too (oldest first).
    'lookback_days' => (int) env('DISBURSEMENTS_LOOKBACK_DAYS', 30),

    'clickpesa' => [
        'base_url' => env('CLICKPESA_API_BASE_URL', 'https://api.clickpesa.com/third-parties'),
        'api_key' => env('CLICKPESA_API_KEY'),
        'client_id' => env('CLICKPESA_CLIENT_ID'),
        'checksum_key' => env('CLICKPESA_CHECKSUM_KEY'),
        'currency' => env('CLICKPESA_PAYOUT_CURRENCY', 'TZS'),
        'timeout' => (int) env('CLICKPESA_TIMEOUT', 45),
        'connect_timeout' => (int) env('CLICKPESA_CONNECT_TIMEOUT', 15),
    ],

];
