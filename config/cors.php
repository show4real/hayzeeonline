<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Read by Illuminate\Http\Middleware\HandleCors, which is prepended to the
    | global stack in bootstrap/app.php. Laravel 11+ does not publish this file
    | by default, but it is still the file the middleware reads.
    |
    | The image paths are here because nginx serves public/images and
    | public/detailedproducts as static files; a cross-origin fetch() of a
    | product photo from the storefront needs the header on those too.
    |
    */

    'paths' => [
        'api/*',
        'storage/*',
        'images/*',
        'detailedproducts/*',
        'up',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Must stay false while allowed_origins is ['*'] — a wildcard origin and
    // credentials together are rejected by every browser, and turning this on
    // would silently break CORS everywhere.
    'supports_credentials' => false,

];
