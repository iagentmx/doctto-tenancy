<?php

use Illuminate\Support\Str;

$base = Str::finish((string) env('N8N_WEBHOOK_BASE_URL', ''), '/');

return [
    'timeout' => (int) env('N8N_TIMEOUT', 10),
    'connect_timeout' => (int) env('N8N_CONNECT_TIMEOUT', 5),
    'api_key' => (string) env('N8N_API_KEY'),
    'webhook' => [
        'update_tenant' => $base . 'update/tenant'
    ]
];
