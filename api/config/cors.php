<?php

return [
    'paths'                    => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods'          => ['*'],
    'allowed_origins'          => array_filter([
        env('FRONTEND_URL', 'http://localhost:3000'),
        'http://localhost:3000',
        'http://localhost:5173',
    ]),
    'allowed_origins_patterns' => [
        '#^https://.*\.netlify\.app$#',
    ],
    'allowed_headers'          => ['*'],
    'exposed_headers'          => [],
    'max_age'                  => 86400,
    'supports_credentials'     => false,
];
