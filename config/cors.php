<?php

return [
    'paths' => ['api/storefront/*'],
    'allowed_methods' => ['GET', 'HEAD', 'OPTIONS'],
    'allowed_origins' => array_values(array_filter(array_map('trim', explode(',', env('STOREFRONT_ALLOWED_ORIGINS', 'http://localhost:3000,http://localhost:3003'))))),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Accept', 'Content-Type', 'Origin'],
    'exposed_headers' => ['Cache-Control'],
    'max_age' => 3600,
    'supports_credentials' => false,
];
