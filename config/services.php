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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'cibil' => [
        'endpoint' => env('CIBIL_API_ENDPOINT', 'https://api.cibil.com/v1/reports'),
        'api_key' => env('CIBIL_API_KEY'),
        'timeout' => env('CIBIL_API_TIMEOUT', 30),
        'retry_attempts' => env('CIBIL_API_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('CIBIL_API_RETRY_DELAY', 1000), // milliseconds
    ],

    'aadhaar' => [
        'api_key' => env('AADHAAR_API_KEY'),
        'endpoint' => env('AADHAAR_API_ENDPOINT', 'https://kyc-api.surepass.io/api/v1/aadhaar-validation/aadhaar-validation'),
        'timeout' => env('AADHAAR_API_TIMEOUT', 30),
        'environment' => env('AADHAAR_API_ENV', 'test'),
    ],

];
