<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Revolut Merchant Environment
    |--------------------------------------------------------------------------
    |
    | Supported values: "sandbox" and "live".
    |
    */
    'environment' => env('REVOLUT_PAYMENTS_ENVIRONMENT', 'sandbox'),

    /*
    |--------------------------------------------------------------------------
    | API Credentials
    |--------------------------------------------------------------------------
    */
    'api_key' => env('REVOLUT_PAYMENTS_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | API Version
    |--------------------------------------------------------------------------
    |
    | Revolut requires a Revolut-Api-Version header for Merchant API calls.
    | Keep this configurable so your application can pin and upgrade versions
    | deliberately.
    |
    */
    'api_version' => env('REVOLUT_PAYMENTS_API_VERSION', '2026-04-20'),

    /*
    |--------------------------------------------------------------------------
    | Base URLs
    |--------------------------------------------------------------------------
    */
    'base_urls' => [
        'sandbox' => env('REVOLUT_PAYMENTS_SANDBOX_URL', 'https://sandbox-merchant.revolut.com/api/1.0'),
        'live' => env('REVOLUT_PAYMENTS_LIVE_URL', 'https://merchant.revolut.com/api/1.0'),
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Client
    |--------------------------------------------------------------------------
    */
    'timeout' => (int) env('REVOLUT_PAYMENTS_TIMEOUT', 15),
    'retry_times' => (int) env('REVOLUT_PAYMENTS_RETRY_TIMES', 2),
    'retry_sleep_milliseconds' => (int) env('REVOLUT_PAYMENTS_RETRY_SLEEP', 250),

    /*
    |--------------------------------------------------------------------------
    | Webhooks
    |--------------------------------------------------------------------------
    |
    | Set this after creating a webhook secret in Revolut Business.
    |
    */
    'webhook_secret' => env('REVOLUT_PAYMENTS_WEBHOOK_SECRET'),
    'webhook_tolerance_seconds' => (int) env('REVOLUT_PAYMENTS_WEBHOOK_TOLERANCE', 300),
];
