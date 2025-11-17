<?php

declare(strict_types=1);

use VildanBina\TranslationPruner\Loaders\JsonLoader;
use VildanBina\TranslationPruner\Loaders\PhpArrayLoader;
use VildanBina\TranslationPruner\Scanners\BladeScanner;
use VildanBina\TranslationPruner\Scanners\PhpScanner;
use VildanBina\TranslationPruner\Scanners\ReactScanner;
use VildanBina\TranslationPruner\Scanners\VueScanner;

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

    // Directories within scan paths that should be ignored
    'ignore' => [
        'vendor',
        'node_modules',
        'storage',
        'bootstrap/cache',
    ],

    // File patterns that should be scanned for translation usage
    'file_patterns' => ['*.php', '*.blade.php', '*.vue', '*.js', '*.ts', '*.jsx', '*.tsx'],

    // Translation loaders used to read/write translation files
    'loaders' => [
        JsonLoader::class,
        PhpArrayLoader::class,
    ],

    // Content scanners used to detect translation keys
    'scanners' => [
        PhpScanner::class,
        BladeScanner::class,
        VueScanner::class,
        ReactScanner::class,
    ],

    // Location of translation files
    'lang_path' => function_exists('base_path') ? base_path('lang') : getcwd().'/lang',

    // Enable debug output
    'debug' => env('APP_DEBUG', true),
];
