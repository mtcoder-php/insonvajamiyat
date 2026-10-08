<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    /*
    | Google / ORCID orqali kirish (Laravel Socialite). client_id bo'sh bo'lsa — tugma ko'rinmaydi.
    | Callback manzillari provayder konsolida aynan shunday ro'yxatdan o'tkaziladi:
    |   {APP_URL}/auth/google/callback, {APP_URL}/auth/orcid/callback
    */
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
    ],

    'orcid' => [
        'client_id' => env('ORCID_CLIENT_ID'),
        'client_secret' => env('ORCID_CLIENT_SECRET'),
        'redirect' => env('ORCID_REDIRECT_URI', '/auth/orcid/callback'),
        // Sinov muhiti: https://sandbox.orcid.org
        'sandbox' => (bool) env('ORCID_SANDBOX', false),
    ],

    // To'lov tizimlari (Click, Payme) — config/payments.php

    // AI xizmatlari (Anthropic Claude API) — config/ai.php

];
