<?php

return [
    'intent_ttl_minutes' => env('PAYMENT_INTENT_TTL_MINUTES', 30),
    'providers' => [
        'mockpay' => [
            'webhook_secret' => env('MOCKPAY_WEBHOOK_SECRET', ''),
        ],
        'khrqr' => [
            'webhook_secret' => env('KHRQR_WEBHOOK_SECRET', ''),
        ],
    ],
];
