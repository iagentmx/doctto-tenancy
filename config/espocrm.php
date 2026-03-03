<?php

return [
    'base_url' => env('ESPOCRM_BASE_URL'),
    'username' => env('ESPOCRM_USERNAME'),
    'password' => env('ESPOCRM_PASSWORD'),
    'timeout_seconds' => (int) env('ESPOCRM_TIMEOUT_SECONDS', 15),
];
