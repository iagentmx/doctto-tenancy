<?php

use Illuminate\Support\Str;

$n8nBase = Str::finish((string) env('N8N_WEBHOOK_BASE_URL', ''), '/');

return [
    'dispatch' => [
        'limit' => (int) env('NOTIFIER_EVENTS_DISPATCH_LIMIT', 100),
    ],
    'destinations' => [
        'n8n' => [
            'enabled' => filter_var(env('NOTIFIER_EVENTS_N8N_ENABLED', true), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? true,
            'webhook_url' => $n8nBase . 'update/tenant',
            'api_key' => (string) env('N8N_API_KEY'),
            'connect_timeout' => (int) env('N8N_CONNECT_TIMEOUT', 5),
            'timeout' => (int) env('N8N_TIMEOUT', 10),
            'max_attempts' => (int) env('NOTIFIER_EVENTS_N8N_MAX_ATTEMPTS', 5),
            'retry_delays_seconds' => [60, 300, 900, 3600],
        ],
    ],
];
