<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'sms' => [
        'username' => env('SMS_API_USERNAME', 'HIGHLINK'),
        'password' => env('SMS_API_PASSWORD', 'ifxcs1ud'),
        'sender_id' => env('SMS_API_SENDER_ID', 'HIGHLINK'),
    ],
    
    'airtel' => [
        'base_url' => env('AIRTEL_API_BASE_URL', 'https://openapi.airtel.africa'),
        'client_id' => env('AIRTEL_CLIENT_ID'),
        'client_secret' => env('AIRTEL_CLIENT_SECRET'),
        'callback_secret' => env('AIRTEL_CALLBACK_SECRET'),
    ],

    'fcm' => [
        // Absolute path to the Firebase Admin SDK service-account JSON.
        // Defaults to storage/app/firebase/firebase-admin.json (gitignored).
        'credentials' => env('FCM_CREDENTIALS', storage_path('app/firebase/firebase-admin.json')),
        'project_id' => env('FCM_PROJECT_ID', 'highlink-b410f'),
        // Optional CA bundle for HTTPS verification on dev hosts lacking one.
        'ca_bundle' => env('FCM_CA_BUNDLE'),
    ],

];
