<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Laravel CORS Options
    |--------------------------------------------------------------------------
    |
    | The allowed_methods and allowed_headers options are case-insensitive.
    |
    | You don't need to provide both allowed_origins and allowed_origins_patterns.
    | If one of the strings passed matches, it is considered a valid origin.
    |
    | If ['*'] is provided to allowed_methods, allowed_origins or allowed_headers
    | all methods / origins / headers are allowed.
    |
    */

    /*
     * You can enable CORS for 1 or multiple paths.
     * Example: ['api/*']
     */
//    'paths' => ['api/*', 'sanctum/csrf-cookie', '/storage/*', '/images/*'],

//     'allowed_methods' => ['*'],

//     'allowed_origins' => ['*'],

//     'allowed_origins_patterns' => ['*'],

//     'allowed_headers' => ['content-type', 'accept', 'x-custom-header', 'Access-Control-Allow-Origin'],

//     'exposed_headers' => ['*'],

//     'max_age' => 0,

//     'supports_credentials' => false,

    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    // 'allowed_origins' => ['http://127.0.0.1:8080/', 'http://localhost:8080/'], <-- doesn't work, still gets CORS error
    'allowed_origins' => ['*'],  // <-- it works but it should not be like that
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['content-type', 'accept', 'x-custom-header', 'Access-Control-Allow-Origin'],
    // 'allowed_headers' => ['*'],
    'exposed_headers' => ['x-custom-response-header'],
    'max_age' => 0,
    'supports_credentials' => false,
];
