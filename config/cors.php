<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | For detailed information about CORS configuration, see:
    | https://laravel.com/docs/middleware#cors
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // Public React applications allowed to call this API. The environment
    // variable lets deployment add domains without changing the source.
    'allowed_origins' => array_filter(array_map('trim', explode(',', env(
        'CORS_ALLOWED_ORIGINS',
        'http://localhost:5173,https://reactfast.passpermisfacile.fr,https://staging2.passpermisfacile.fr'
    )))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Important: allow credentials for cookies / auth
    'supports_credentials' => true,
];
