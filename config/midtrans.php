<?php

return [
    'server_key' => env('MIDTRANS_SERVER_KEY'),
    'client_key' => env('MIDTRANS_CLIENT_KEY'),

    'is_production' => env('MIDTRANS_IS_PRODUCTION', false),

    'sanitise' => env('MIDTRANS_IS_SANITIZED', true),
    '3ds' => env('MIDTRANS_IS_3DS', true),

    'webhook_url' => env('MIDTRANS_WEBHOOK_URL', env('APP_URL') . '/api/midtrans-webhook'),

    'finish_redirect_url' => env('MIDTRANS_FINISH_REDIRECT_URL', env('APP_URL') . '/payment-status'),
];
