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
    | Paths to Scan
    |--------------------------------------------------------------------------
    |
    | These are the directories that will be scanned for translation usage.
    | By default, it scans your app directory, views, and JavaScript files.
    | You can add or remove paths as needed for your application structure.
    |
    */
    'paths' => [
        base_path('app'),
        base_path('resources/views'),
        base_path('resources/js'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Protected Translation Keys
    |--------------------------------------------------------------------------
    |
    | These translation keys will never be deleted, even if they appear unused.
    | This protects Laravel core translations and popular packages like
    | Filament and Nova from being accidentally removed.
    |
    */
    'exclude' => [
        'validation.*',
        'auth.*',
        'pagination.*',
        'passwords.*',
        'filament.*',
        'nova.*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignored Directories
    |--------------------------------------------------------------------------
    |
    | These directories within your scan paths will be ignored during scanning.
    | This prevents scanning vendor directories, cache files, and other
    | directories that don't contain your application code.
    |
    */
    'ignore' => [
        'vendor',
        'node_modules',
        'storage',
        'bootstrap/cache',
    ],

    /*
    |--------------------------------------------------------------------------
    | File Patterns to Scan
    |--------------------------------------------------------------------------
    |
    | These are the file extensions that will be scanned for translation usage.
    | The pruner will look for translation function calls in files matching
    | these patterns within your configured scan paths.
    |
    */
    'file_patterns' => ['*.php', '*.blade.php', '*.vue', '*.js', '*.ts', '*.jsx', '*.tsx'],

    /*
    |--------------------------------------------------------------------------
    | Translation Loaders
    |--------------------------------------------------------------------------
    |
    | These classes are responsible for reading and writing translation files.
    | The JsonLoader handles JSON translation files, while PhpArrayLoader
    | handles PHP array translation files. You can add custom loaders here.
    |
    */
    'loaders' => [
        JsonLoader::class,
        PhpArrayLoader::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Scanners
    |--------------------------------------------------------------------------
    |
    | These classes are responsible for detecting translation keys in your code.
    | Each scanner handles different file types and patterns. You can add
    | custom scanners here to support additional frameworks or patterns.
    |
    */
    'scanners' => [
        PhpScanner::class,
        BladeScanner::class,
        VueScanner::class,
        ReactScanner::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Translation Files Path
    |--------------------------------------------------------------------------
    |
    | This is the path where your translation files are stored. By default,
    | it uses the standard Laravel lang directory. You can customize this
    | if your translation files are stored elsewhere.
    |
    */
    'lang_path' => function_exists('base_path') ? base_path('lang') : getcwd().'/lang',

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, this will output detailed information about the scanning
    | and pruning process. This is helpful for troubleshooting or understanding
    | which translations are being used or removed.
    |
    */
    'debug' => env('APP_DEBUG', true),
];
