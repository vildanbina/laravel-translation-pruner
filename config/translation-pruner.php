<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Translation Pruner Configuration
    |--------------------------------------------------------------------------
    |
    | Simple configuration for finding and removing unused translations.
    |
    */

    // Paths to scan for translation usage
    'paths' => [
        base_path('app'),
        base_path('resources/views'),
        base_path('resources/js'),
    ],

    // Translation keys to never delete (Laravel core translations)
    'exclude' => [
        'validation.*',
        'auth.*',
        'pagination.*',
        'passwords.*',
        'filament.*',
        'nova.*',
    ],

    // Enable debug output
    'debug' => env('APP_DEBUG', true),
];
