<?php

return [
    /*
    |--------------------------------------------------------------------------
    | License enforcement
    |--------------------------------------------------------------------------
    |
    | When enabled, schools must have an active license (or be in grace period)
    | to use protected API routes. Disable locally; enable in production SaaS.
    |
    */
    'enforcement' => env('LICENSE_ENFORCEMENT', false),

    /*
    |--------------------------------------------------------------------------
    | Grace period after expiry (days)
    |--------------------------------------------------------------------------
    |
    | Schools can still access the system briefly after expiry so admins can
    | enter a renewal key via POST /api/v1/license/activate.
    |
    */
    'grace_days' => (int) env('LICENSE_GRACE_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Key prefix shown to customers
    |--------------------------------------------------------------------------
    */
    'key_prefix' => env('LICENSE_KEY_PREFIX', 'SKERP'),

    /*
    |--------------------------------------------------------------------------
    | Default plan durations (months)
    |--------------------------------------------------------------------------
    */
    'plans' => [
        'lifetime' => null,
        'monthly' => 1,
        'quarterly' => 3,
        'annual' => 12,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default list prices (platform SaaS revenue recognition)
    |--------------------------------------------------------------------------
    |
    | Used when generating license keys without an explicit amount. Currency is
    | platform-wide for summary reporting.
    |
    */
    'currency' => env('LICENSE_CURRENCY', 'USD'),
    'prices' => [
        'lifetime' => (float) env('LICENSE_PRICE_LIFETIME', 2500),
        'monthly' => (float) env('LICENSE_PRICE_MONTHLY', 49),
        'quarterly' => (float) env('LICENSE_PRICE_QUARTERLY', 129),
        'annual' => (float) env('LICENSE_PRICE_ANNUAL', 449),
        'custom' => (float) env('LICENSE_PRICE_CUSTOM', 0),
    ],
];
