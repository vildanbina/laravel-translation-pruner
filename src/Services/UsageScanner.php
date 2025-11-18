<?php

declare(strict_types=1);

namespace VildanBina\TranslationPruner\Services;

use Exception;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Symfony\Component\Finder\Finder;
use VildanBina\TranslationPruner\Contracts\ScannerInterface;
use VildanBina\TranslationPruner\Scanners\BladeScanner;
use VildanBina\TranslationPruner\Scanners\PhpScanner;
use VildanBina\TranslationPruner\Scanners\ReactScanner;
use VildanBina\TranslationPruner\Scanners\VueScanner;

class UsageScanner
{
    /**
     * @var array<int, ScannerInterface>
     */
    private array $scanners = [];

    /**
     * @var array<int, string>
     */
    private array $excludeDirectories = [];

    /**
     * @var array<int, string>
     */
    private array $filePatterns = [];

    public function __construct(ConfigRepository $config)
    {
        /** @var array<string, mixed> $settings */
        $settings = (array) $config->get('translation-pruner', []);

        $this->scanners = $this->instantiateScanners($settings['scanners'] ?? null);
        $this->excludeDirectories = $this->sanitizeStrings(
            $settings['ignore'] ?? null,
            ['vendor', 'node_modules', 'storage', 'bootstrap/cache']
        );
        $this->filePatterns = $this->sanitizeStrings(
            $settings['file_patterns'] ?? null,
            ['*.php', '*.blade.php', '*.vue', '*.js', '*.ts', '*.jsx', '*.tsx']
        );
    }

    /**
     * @param  array<int, string>  $paths
     * @return array<int, string>
     */
    public function scan(array $paths): array
    {
        $paths = $this->filterPaths($paths);

        if (empty($paths)) {
            return [];
        }

        $keys = [];

        try {
            $finder = (new Finder)
                ->files()
                ->in($paths)
                ->ignoreUnreadableDirs();

            foreach ($this->filePatterns as $pattern) {
                $finder->name($pattern);
            }

            foreach ($this->excludeDirectories as $directory) {
                $finder->exclude($directory);
            }

            foreach ($finder as $file) {
                $scanners = $this->resolveScanners($file->getFilename());

                if (empty($scanners)) {
                    continue;
                }

                $contents = $file->getContents();

                foreach ($scanners as $scanner) {
                    foreach ($scanner->scan($contents) as $key) {
                        $keys[$key] = true;
                    }
                }
            }
        } catch (Exception $e) {
            // Silently ignore filesystem issues to keep command output clean.
        }

        return array_keys($keys);
    }

    /**
     * @return array<int, ScannerInterface>
     */
    private function resolveScanners(string $fileName): array
    {
        return array_values(array_filter(
            $this->scanners,
            static fn (ScannerInterface $scanner): bool => $scanner->canHandle($fileName)
        ));
    }

    /**
     * @param  array<int, string>  $paths
     * @return array<int, string>
     */
    private function filterPaths(array $paths): array
    {
        $filtered = array_filter($paths, static fn (string $path): bool => is_dir($path));

        return array_values(array_unique($filtered));
    }

    /**
     * @return array<int, ScannerInterface>
     */
    private function instantiateScanners(mixed $classes): array
    {
        $classList = $this->sanitizeScannerClasses($classes);

        if (empty($classList)) {
            return $this->defaultScanners();
        }

        return array_map(
            static fn (string $class): ScannerInterface => new $class,
            $classList
        );
    }

    /**
     * @return array<int, ScannerInterface>
     */
    private function defaultScanners(): array
    {
        return [
            new PhpScanner,
            new BladeScanner,
            new VueScanner,
            new ReactScanner,
        ];
    }

    /**
     * @return array<int, class-string<ScannerInterface>>
     */
    private function sanitizeScannerClasses(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        return array_values(array_filter(
            $values,
            static fn ($value): bool => is_string($value)
                && $value !== ''
                && class_exists($value)
                && is_subclass_of($value, ScannerInterface::class)
        ));
    }

    /**
     * @param  array<int, string>  $default
     * @return array<int, string>
     */
    private function sanitizeStrings(mixed $values, array $default): array
    {
        if (! is_array($values)) {
            return $default;
        }

        $filtered = array_values(array_filter(
            $values,
            static fn ($value): bool => is_string($value) && $value !== ''
        ));

        return $filtered === [] ? $default : $filtered;
    }
}
