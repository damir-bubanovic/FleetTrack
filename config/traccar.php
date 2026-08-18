<?php

return [
    'url' => env('TRACCAR_URL'),

    'username' => env('TRACCAR_USERNAME'),

    'password' => env('TRACCAR_PASSWORD'),

    'timeout' => (int) env('TRACCAR_TIMEOUT', 30),

    'verify_ssl' => (bool) env('TRACCAR_VERIFY_SSL', true),
];
