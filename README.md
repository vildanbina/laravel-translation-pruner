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

### Scan for unused translations

```bash
# See what's unused (dry run by default)
php artisan translation:scan

# Save results to file
php artisan translation:scan --save=unused.json
```

### Remove unused translations

```bash
# Preview what will be deleted
php artisan translation:prune --dry-run

# Actually delete them (will ask for confirmation)
php artisan translation:prune

# Delete without asking for confirmation
php artisan translation:prune --force
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

## Features

- ✅ Scans PHP, Blade, Vue, and JavaScript files
- ✅ Handles both array and JSON translation files
- ✅ Safe dry-run mode
- ✅ Configurable exclusion patterns
- ✅ Simple and fast

## Example output

```
Scanning for translations...
+---------------------+-------+
| Metric              | Count |
+---------------------+-------+
| Total translations  | 156   |
| Used translations   | 98    |
| Unused translations | 58    |
+---------------------+-------+

Unused translations:
  • messages.old_welcome
  • auth.unused_button
  • validation.custom_field
```

## Contributing

Feel free to submit issues and pull requests.

## License

MIT