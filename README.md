# Laravel Translation Pruner

[![Latest Stable Version](https://poser.pugx.org/vildanbina/laravel-translation-pruner/v)](https://packagist.org/packages/vildanbina/laravel-translation-pruner)
[![Total Downloads](https://poser.pugx.org/vildanbina/laravel-translation-pruner/downloads)](https://packagist.org/packages/vildanbina/laravel-translation-pruner)
[![License](https://poser.pugx.org/vildanbina/laravel-translation-pruner/license)](https://packagist.org/packages/vildanbina/laravel-translation-pruner)
[![PHP Version Require](https://poser.pugx.org/vildanbina/laravel-translation-pruner/require/php)](https://packagist.org/packages/vildanbina/laravel-translation-pruner)
![GitHub Workflow Status (main)](https://img.shields.io/github/actions/workflow/status/vildanbina/laravel-translation-pruner/ci.yml?label=Tests)

A simple Laravel package to find and remove unused translations from your codebase.

## Installation

```bash
composer require vildanbina/laravel-translation-pruner
```

Publish the config:

```bash
php artisan vendor:publish --tag="translation-pruner-config"
```

## Usage

```bash
# Delete unused translations (will ask for confirmation)
php artisan translation:prune

# Delete without asking for confirmation
php artisan translation:prune --force

# Preview what would be deleted without actually deleting
php artisan translation:prune --dry-run

# Limit scanning to specific folders
php artisan translation:prune --path=app --path=modules/Blog
```

## Configuration

Edit `config/translation-pruner.php`:

```php
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
```

## What it scans

The package looks for translations in:

**PHP files:**
- `__('messages.welcome')`
- `trans('auth.login')`
- `Lang::get('validation.required')`

**Blade templates:**
- `@lang('messages.welcome')`
- `@choice('messages.items', $count)`
- `{{ __('messages.welcome') }}`
- `:<div :title="__('messages.tooltip')">`

**Vue/JavaScript files:**
- `$t('messages.welcome')`
- `i18n.t('auth.login')`
- `v-t="messages.welcome"`

**React (JSX/TSX) files:**
- `t('messages.welcome')`
- `<Trans i18nKey="auth.login" />`
- `<FormattedMessage id="auth.login" />`

## Features

- ✅ Scans PHP, Blade/Livewire, Vue, and React files
- ✅ Handles both array and JSON translation files
- ✅ Safe dry-run mode
- ✅ Configurable exclusion patterns
- ✅ Extensible loaders/scanners and file globs
- ✅ Simple and fast

## Example output

```
Finding unused translations...
Found 58 unused translation entries:
  • messages.old_welcome (en)
  • auth.unused_button (en, de)
  • validation.custom_field (en)
  ...

Delete these unused translations? (yes/no) [no]:
> yes

✅ Deleted 58 unused translation entries
```


## Contributing

See [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please e-mail vildanbina@gmail.com to report any security vulnerabilities instead of using the issue tracker.

## Credits

- [Vildan Bina](https://github.com/vildanbina)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
