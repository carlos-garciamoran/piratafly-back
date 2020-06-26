<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => [
        'GET',
        'POST',
        'OPTIONS',
        // 'PUT',
        // 'PATCH',
        // 'DELETE',
    ],

    'allowed_origins' => [
        env('AUTH0_API_IDENTIFIER'),
        env('APP_ENV') === 'local' ? 'http://localhost:5000' : '',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Authorization',
        'Content-Type',
        'Origin',
        'Accept',
    ],

    'exposed_headers' => [
        'Cache-Control',
        'Content-Type',
        'Expires',
        'Last-Modified',
    ],

    // How long the response to the preflight request can be cached for without
    // sending another preflight request. Default = 86400 seconds (24 hours)
    'max_age' => 60 * 60 * 24,

    'supports_credentials' => false,

];
