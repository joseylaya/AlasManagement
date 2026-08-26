<?php

return [
    'paths' => ['api/storefront/*', 'api/v1/storefront/*', 'api/v1/support/*'],
    'allowed_methods' => ['GET', 'HEAD', 'POST', 'OPTIONS'],
    'allowed_origins' => array_values(array_filter(array_map('trim', explode(',', env('STOREFRONT_ALLOWED_ORIGINS', 'http://localhost:3000,http://localhost:3003'))))),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Accept', 'Authorization', 'Content-Type', 'Idempotency-Key', 'Origin', 'X-Support-Token'],
    'exposed_headers' => ['Cache-Control'],
    'max_age' => 3600,
    'supports_credentials' => false,
];
