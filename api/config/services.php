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

    //OAUTH
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', rtrim(env('APP_URL', 'http://localhost'), '/') . '/auth/google/callback'),
    ],
    'facebook' => [
        'client_id' => env('FACEBOOK_APP_ID'),
        'client_secret' => env('FACEBOOK_APP_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI', rtrim(env('APP_URL', 'http://localhost'), '/') . '/auth/facebook/callback'),
        'graph_version' => env('FACEBOOK_GRAPH_VERSION', 'v19.0'),
    ],
    'github' => [
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect' => env('GITHUB_REDIRECT_URI', rtrim(env('APP_URL', 'http://localhost'), '/') . '/auth/github/callback'),
    ],
    'telegram-bot-api' => [
        'token' => env('TELEGRAM_BOT_TOKEN', env('TELEGRAM_BOT_TOKEN')),
        'username' => env('TELEGRAM_BOT_USERNAME', env('TELEGRAM_BOT_NAME')),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
        'admin_chat_id' => env('TELEGRAM_ADMIN_CHAT_ID'),
        'link_ttl_minutes' => env('TELEGRAM_LINK_TTL_MINUTES', 10),
        'base_uri' => env('TELEGRAM_BOT_BASE_URI', 'https://api.telegram.org'),
        'send_inline' => env('TELEGRAM_SEND_INLINE', false),
        'log_delivery' => env('TELEGRAM_LOG_DELIVERY', false),
    ],
    'telegram' => [
        'bot' => env('TELEGRAM_BOT_NAME'),  // The bot's username
        'client_id' => null,
        'client_secret' => env('TELEGRAM_BOT_TOKEN'),
        'redirect' => env('TELEGRAM_REDIRECT_URI', rtrim(env('APP_URL', 'https://127.0.0.1:8000'), '/') . '/auth/telegram/callback'),
    ]

];
