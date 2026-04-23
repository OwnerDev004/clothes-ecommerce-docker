<?php

return [
    'intent_ttl_minutes' => env('PAYMENT_INTENT_TTL_MINUTES', 30),
    'providers' => [
        'mockpay' => [
            'webhook_secret' => env('MOCKPAY_WEBHOOK_SECRET', ''),
            'webhook_enabled' => env('MOCKPAY_WEBHOOK_ENABLED', true),
        ],
        'khrqr' => [
            'webhook_secret' => env('KHRQR_WEBHOOK_SECRET', ''),
            'webhook_enabled' => env('KHRQR_WEBHOOK_ENABLED', false),
            'check_payment_endpoint' => env('KHRQR_CHECK_PAYMENT_ENDPOINT', ''),
            'api_key' => env('KHRQR_API_KEY', ''),
            'poll_timeout' => env('KHRQR_POLL_TIMEOUT', 10),
            'token' => env('KHRQR_API_TOKEN', ''),
            'token_sit' => env('KHRQR_API_TOKEN_SIT', ''),
            'use_sit' => env('KHRQR_USE_SIT', false),
            'deeplink_url' => env('KHRQR_DEEPLINK_URL', ''),
            'deeplink_app_icon_url' => env('KHRQR_DEEPLINK_APP_ICON_URL', ''),
            'deeplink_app_name' => env('KHRQR_DEEPLINK_APP_NAME', ''),
            'deeplink_app_deep_link_callback' => env('KHRQR_DEEPLINK_APP_DEEP_LINK_CALLBACK', ''),
            'merchant' => [
                'account_id' => env('KHRQR_MERCHANT_ACCOUNT_ID', 'kry_longdy@aclb'),
                'merchant_name' => env('KHRQR_MERCHANT_NAME', 'Longdy Kry'),
                'merchant_city' => env('KHRQR_MERCHANT_CITY', 'Takeo'),
                'merchant_id' => env('KHRQR_MERCHANT_ID', '101376578'),
                'acquiring_bank' => env('KHRQR_ACQUIRING_BANK', 'Dev Bank'),
                'mobile_number' => env('KHRQR_MOBILE_NUMBER', '85586382575'),
                'store_label' => env('KHRQR_STORE_LABEL', 'Longdy Shop'),
                'terminal_label' => env('KHRQR_TERMINAL_LABEL', 'WEB'),
                'purpose' => env('KHRQR_PURPOSE', 'Online purchase'),
                'account_information' => env('KHRQR_ACCOUNT_INFORMATION', null),
                'upi_merchant_account' => env('KHRQR_UPI_MERCHANT_ACCOUNT', null),
            ],
        ],
    ],
];
