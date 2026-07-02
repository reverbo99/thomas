<?php

return [
    /*
    |--------------------------------------------------------------------------
    | TRA Electronic Fiscal Device (VFD) — Tanzania
    |--------------------------------------------------------------------------
    |
    | Error "Missing Cert-Serial Header or provided certificate not found" means:
    | 1) TRA_CERT_PATH must be the exact .pfx issued to you for this TIN (production vs test).
    | 2) TRA_CERT_SERIAL must match the EFD serial on that certificate (same as TRA portal).
    | 3) TRA_ENV must match where that cert is registered (production: vfd.tra.go.tz).
    | 4) If auto header still fails, set TRA_CERT_SERIAL_HEADER_BASE64 to the value TRA gave you.
    | 5) After changing TIN, env, or certificate, delete storage/app/tra/state.json once.
    |
    | Set TRA_ENABLED=false to process payments without fiscalization until TRA is fixed.
    |
    */
    'enabled' => env('TRA_ENABLED', true),

    'env' => env('TRA_ENV', 'production'), // test or production
    'tin' => env('TRA_TIN', ''),
    'cert_path' => env('TRA_CERT_PATH', storage_path('app/tra/certificate.pfx')),
    'password' => env('TRA_PASSWORD'),
    // EFD serial string (CERTKEY in registration XML), e.g. 10TZ… — must match the .pfx file
    'cert_serial' => env('TRA_CERT_SERIAL', ''),

    // Cert-Serial header encoding: hex_string (TRA/golang default) or hex_bytes (legacy).
    'cert_serial_header_mode' => env('TRA_CERT_SERIAL_HEADER_MODE', 'hex_string'),

    // HTTP client header value per TRA API docs.
    'client' => env('TRA_CLIENT', 'webapi'),

    // Set false on local WAMP if cURL error 60 (missing CA bundle). Keep true in production.
    'verify_ssl' => env('TRA_VERIFY_SSL', true),
    'timeout' => env('TRA_TIMEOUT', 60),
    'connect_timeout' => env('TRA_CONNECT_TIMEOUT', 20),

    /*
    | Test URLs from TRA integration email (vfdtest.tra.go.tz).
    | Legacy virtual.tra.go.tz endpoints are NOT used for this TIN/cert.
    */
    'urls' => [
        'test' => [
            'register' => env('TRA_TEST_REGISTER_URL', 'https://vfdtest.tra.go.tz/api/vfdregreq'),
            'token' => env('TRA_TEST_TOKEN_URL', 'https://vfdtest.tra.go.tz/vfdtoken'),
            'receipt' => env('TRA_TEST_RECEIPT_URL', 'https://vfdtest.tra.go.tz/api/efdmsrctinfo'),
            'zreport' => env('TRA_TEST_ZREPORT_URL', 'https://vfdtest.tra.go.tz/api/efdmszreport'),
            'verify' => env('TRA_TEST_VERIFY_URL', 'https://virtual.tra.go.tz/efdmsrctverify'),
        ],
        'production' => [
            'register' => env('TRA_PROD_REGISTER_URL', 'https://vfd.tra.go.tz/api/vfdRegReq'),
            'token' => env('TRA_PROD_TOKEN_URL', 'https://vfd.tra.go.tz/vfdtoken'),
            'receipt' => env('TRA_PROD_RECEIPT_URL', 'https://vfd.tra.go.tz/api/efdmsRctInfo'),
            'zreport' => env('TRA_PROD_ZREPORT_URL', 'https://vfd.tra.go.tz/api/efdmszreport'),
            'verify' => env('TRA_PROD_VERIFY_URL', 'https://verify.tra.go.tz'),
        ],
    ],

    // Deprecated — use urls.* above
    'base_url' => [
        'test' => 'https://vfdtest.tra.go.tz/api',
        'production' => 'https://vfd.tra.go.tz/api',
    ],
    'verify_url' => [
        'test' => 'https://virtual.tra.go.tz/efdmsrctverify',
        'production' => 'https://verify.tra.go.tz',
    ],
];
