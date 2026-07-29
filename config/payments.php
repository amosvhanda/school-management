<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Live payment gateway
    |--------------------------------------------------------------------------
    |
    | When false (default), POST /platform/payments/initiate refuses to create
    | gateway transactions. Manual payments via /payments remain available.
    | Enable only after a real provider (Paynow/EcoCash/Stripe) is wired and
    | webhook verification is configured.
    |
    */
    'gateway_live' => env('PAYMENT_GATEWAY_LIVE', false),

    /*
    |--------------------------------------------------------------------------
    | Webhook shared secret
    |--------------------------------------------------------------------------
    |
    | Required when gateway_live is true. completeWebhook refuses empty secrets.
    |
    */
    'webhook_secret' => env('PAYMENT_GATEWAY_WEBHOOK_SECRET'),
];
