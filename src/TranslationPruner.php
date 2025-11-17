<?php

declare(strict_types=1);

namespace VildanBina\TranslationPruner;

use Exception;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

class TranslationPruner
{
    private array $scanners;

    private array $usedKeys = [];

    private array $availableKeys = [];

    private array $excludePatterns = [];

    public function __construct(array $excludePatterns = [])
    {
        $this->scanners = [
            new Scanners\PhpScanner(),
            new Scanners\BladeScanner(),
            new Scanners\VueScanner(),
        ];

        $this->excludePatterns = $excludePatterns;
    }

    public function scan(array $paths = []): array
    {
        // Reset state
        $this->usedKeys = [];
        $this->availableKeys = [];

        // Use provided paths or sensible defaults
        if (empty($paths)) {
            $paths = $this->getDefaultPaths();
        }

        $this->loadAllTranslations();

        if (! empty($paths)) {
            $this->findUsedKeys($paths);
        }

        $unused = array_diff_key($this->availableKeys, $this->usedKeys);
        $unused = $this->applyExclusions($unused);

        return [
            'total' => count($this->availableKeys),
            'used' => count($this->usedKeys),
            'unused' => count($unused),
            'unused_keys' => $unused,
        ];
    }

    public function prune(array $unusedKeys, bool $dryRun = true): int
    {
        if ($dryRun) {
            return count($unusedKeys);
        }

        $deleted = 0;

        foreach ($unusedKeys as $key => $locales) {
            foreach ($locales as $locale => $info) {
                if ($this->removeKeyFromFile($info['file'], $key, $info['group'])) {
                    $deleted++;
                }
            }
        }

        return $deleted;
    }

    private function getDefaultPaths(): array
    {
        $defaults = [];

        if (function_exists('base_path')) {
            $defaults[] = base_path('app');
            $defaults[] = base_path('resources');
        }

        return array_filter($defaults, 'is_dir');
    }

    private function loadAllTranslations(): void
    {
        $langPath = function_exists('lang_path') ? lang_path() : getcwd().'/lang';

        if (! is_dir($langPath)) {
            return;
        }

        // Load JSON translations
        foreach (glob($langPath.'/*.json') ?: [] as $file) {
            $locale = basename($file, '.json');
            $translations = json_decode(file_get_contents($file), true) ?? [];

            foreach ($translations as $key => $value) {
                $this->availableKeys[$key][$locale] = [
                    'file' => $file,
                    'group' => 'json',
                    'value' => $value,
                ];
            }
        }

        // Load array translations
        foreach (glob($langPath.'/*', GLOB_ONLYDIR) ?: [] as $localeDir) {
            $locale = basename($localeDir);

            foreach (glob($localeDir.'/*.php') ?: [] as $file) {
                $group = basename($file, '.php');
                $translations = include $file;

                if (! is_array($translations)) {
                    continue;
                }

                foreach ($translations as $key => $value) {
                    $fullKey = $group.'.'.$key;
                    $this->availableKeys[$fullKey][$locale] = [
                        'file' => $file,
                        'group' => $group,
                        'value' => $value,
                    ];
                }
            }
        }
    }

    private function findUsedKeys(array $paths): void
    {
        try {
            $finder = (new Finder())
                ->files()
                ->in($paths)
                ->name('*.php')
                ->name('*.blade.php')
                ->name('*.vue')
                ->name('*.js')
                ->name('*.ts')
                ->exclude('vendor')
                ->exclude('node_modules')
                ->ignoreUnreadableDirs();

            foreach ($finder as $file) {
                $scanner = $this->getScannerFor($file);

                if (! $scanner) {
                    continue;
                }

                $keys = $scanner->scan($file->getContents());

                foreach ($keys as $key) {
                    $this->usedKeys[$key] = true;
                }
            }
        } catch (Exception $e) {
            // Silently handle directory issues
        }
    }

    private function getScannerFor(SplFileInfo $file): ?object
    {
        $filename = $file->getFilename();

        // Blade files need special handling since they end with .blade.php
        if (str_ends_with($filename, '.blade.php')) {
            foreach ($this->scanners as $scanner) {
                if ($scanner instanceof Scanners\BladeScanner) {
                    return $scanner;
                }
            }
        }

        // For other files, use the extension
        $extension = $file->getExtension();

        foreach ($this->scanners as $scanner) {
            if ($scanner->canHandle($extension)) {
                return $scanner;
            }
        }

        return null;
    }

    private function removeKeyFromFile(string $file, string $key, string $group): bool
    {
        if ($group === 'json') {
            return $this->removeJsonKey($file, $key);
        }

        return $this->removeArrayKey($file, $key, $group);
    }

    private function removeJsonKey(string $file, string $key): bool
    {
        $translations = json_decode(file_get_contents($file), true);

        if (! isset($translations[$key])) {
            return false;
        }

        unset($translations[$key]);

        if (empty($translations)) {
            return unlink($file);
        }

        $content = json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return file_put_contents($file, $content) !== false;
    }

    private function removeArrayKey(string $file, string $fullKey, string $group): bool
    {
        $translations = include $file;
        $keyParts = explode('.', $fullKey, 2);
        $key = $keyParts[1] ?? $fullKey;

        if (! isset($translations[$key])) {
            return false;
        }

        unset($translations[$key]);

        if (empty($translations)) {
            return unlink($file);
        }

        $content = "<?php\n\nreturn ".var_export($translations, true).";\n";

        return file_put_contents($file, $content) !== false;
    }

    private function applyExclusions(array $unused): array
    {
        if (empty($this->excludePatterns)) {
            return $unused;
        }

        foreach ($unused as $key => $locales) {
            foreach ($this->excludePatterns as $pattern) {
                if ($this->matchesPattern($key, $pattern)) {
                    unset($unused[$key]);
                    break;
                }
            }
        }

        return $unused;
    }

    private function matchesPattern(string $key, string $pattern): bool
    {
        $regex = '/^'.str_replace('\*', '.*', preg_quote($pattern, '/')).'$/';

        return (bool) preg_match($regex, $key);
    }
}
