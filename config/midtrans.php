<?php

return [
    'server_key' => env('SB-Mid-server-GcazxfReNnGfVS9Nnr0iJtko'),
    'client_key' => env('SB-Mid-client-LG_x2OUyXG98nsff'),

    'is_production' => env('MIDTRANS_IS_PRODUCTION', false),

    'is_sanitized' => true,
    'is_3ds' => true,

    'webhook_url' => env('APP_URL') . '/api/midtrans/webhook',

    'finish_redirect_url' => env('APP_URL') . '/payment/success',
];
