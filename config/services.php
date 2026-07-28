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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'sms' => [
        'enabled' => env('SMS_ENABLED', false),
        'provider' => env('SMS_PROVIDER', 'log'),
        'twilio_sid' => env('SMS_TWILIO_SID'),
        'twilio_token' => env('SMS_TWILIO_TOKEN'),
        'twilio_from' => env('SMS_TWILIO_FROM'),
        'africastalking_username' => env('SMS_AT_USERNAME'),
        'africastalking_api_key' => env('SMS_AT_API_KEY'),
        'africastalking_from' => env('SMS_AT_FROM'),
    ],

    'whatsapp' => [
        'enabled' => env('WHATSAPP_ENABLED', false),
        'provider' => env('WHATSAPP_PROVIDER', 'log'),
        'twilio_sid' => env('WHATSAPP_TWILIO_SID'),
        'twilio_token' => env('WHATSAPP_TWILIO_TOKEN'),
        'twilio_from' => env('WHATSAPP_TWILIO_FROM', 'whatsapp:+14155238886'),
        'twilio_content_sid' => env('WHATSAPP_TWILIO_CONTENT_SID'),
        'twilio_content_variables' => env('WHATSAPP_TWILIO_CONTENT_VARIABLES'),
        'use_template' => env('WHATSAPP_TWILIO_USE_TEMPLATE', false),
    ],

    'payments' => [
        'mode' => env('PAYMENTS_MODE', 'sandbox'),
    ],

    'paynow' => [
        'initiate_url' => env('PAYNOW_INITIATE_URL', 'https://www.paynow.co.zw/interface/initiatetransaction'),
        'remote_url' => env('PAYNOW_REMOTE_URL', 'https://www.paynow.co.zw/interface/remotetransaction'),
        'timeout' => (int) env('PAYNOW_TIMEOUT', 30),
    ],

];
