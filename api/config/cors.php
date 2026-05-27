<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_unique(array_filter([
        env('FRONTEND_URL', 'https://clothes-ecommerce-docker.vercel.app'),
        env('FRONTEND_URL_LOCAL', 'http://localhost:3000'),
        env('FRONTEND_URL_ALT', 'http://127.0.0.1:3000'),
    ]))),

    'allowed_origins_patterns' => [
        '#^https:\/\/([a-z0-9-]+\.)*vercel\.app$#i',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
