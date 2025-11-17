# Laravel Translation Pruner

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

return [
    // Paths to scan for translation usage
    'paths' => [
        base_path('app'),
        base_path('resources/views'),
        base_path('resources/js'),
    ],

    // Translation keys to never delete
    'exclude' => [
        'validation.*',
        'auth.*',
        'pagination.*',
        'filament.*',
    ],

    // Directories within the scan paths to skip entirely
    'ignore' => [
        'vendor',
        'node_modules',
    ],

    // File patterns to scan
    'file_patterns' => ['*.php', '*.blade.php', '*.vue', '*.js', '*.ts', '*.jsx', '*.tsx'],

    // Swap or extend the translation loaders/scanners as needed
    'loaders' => [
        \VildanBina\TranslationPruner\Loaders\JsonLoader::class,
        \VildanBina\TranslationPruner\Loaders\PhpArrayLoader::class,
    ],

    'scanners' => [
        \VildanBina\TranslationPruner\Scanners\PhpScanner::class,
        \VildanBina\TranslationPruner\Scanners\BladeScanner::class,
        \VildanBina\TranslationPruner\Scanners\VueScanner::class,
        \VildanBina\TranslationPruner\Scanners\ReactScanner::class,
    ],

    // Optional custom lang path (defaults to base_path('lang'))
    'lang_path' => base_path('lang'),

    // Enable debug output
    'debug' => env('APP_DEBUG', false),
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
- `{{ __('messages.welcome') }}`

**Vue/JavaScript files:**
- `$t('messages.welcome')`
- `i18n.t('auth.login')`

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

Feel free to submit issues and pull requests.

## License

MIT
