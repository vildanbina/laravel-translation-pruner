<?php

declare(strict_types=1);

namespace VildanBina\TranslationPruner;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use VildanBina\TranslationPruner\Services\TranslationRepository;
use VildanBina\TranslationPruner\Services\UsageScanner;

class TranslationPruner
{
    private TranslationRepository $repository;

    private UsageScanner $usageScanner;

    /**
     * @var array<int, string>
     */
    private array $excludePatterns = [];

    /**
     * @var array<int, string>
     */
    private array $defaultPaths = [];

    public function __construct(
        ConfigRepository $config,
        TranslationRepository $repository,
        UsageScanner $usageScanner,
    ) {
        $settings = (array) $config->get('translation-pruner', []);

        $this->repository = $repository;
        $this->usageScanner = $usageScanner;
        $this->excludePatterns = $settings['exclude'] ?? [
            'validation.*',
            'auth.*',
            'pagination.*',
            'passwords.*',
        ];
        $this->defaultPaths = $settings['paths'] ?? [];
    }

    /**
     * @param  array<int, string>  $paths
     */
    public function scan(array $paths = []): array
    {
        $availableKeys = $this->repository->all();
        $resolvedPaths = $this->resolvePaths($paths);
        $usedKeys = $this->usageScanner->scan($resolvedPaths);
        $usedLookup = array_fill_keys($usedKeys, true);

        $unused = $this->applyExclusions(array_diff_key($availableKeys, $usedLookup));
        $usedCount = count(array_intersect_key($usedLookup, $availableKeys));

        return [
            'total' => count($availableKeys),
            'used' => $usedCount,
            'unused' => count($unused),
            'unused_keys' => $unused,
        ];
    }

    public function prune(array $unusedKeys, bool $dryRun = true): int
    {
        if ($dryRun) {
            return $this->countEntries($unusedKeys);
        }

        $deleted = 0;

        foreach ($unusedKeys as $key => $locales) {
            foreach ($locales as $info) {
                $targetKey = $info['key_path'] ?? $key;

                if ($this->removeKeyFromFile($info['file'], $targetKey, $info['group'] ?? null)) {
                    $deleted++;
                }
            }
        }

        return $deleted;
    }

    /**
     * @param  array<int, string>  $paths
     * @return array<int, string>
     */
    private function resolvePaths(array $paths): array
    {
        $paths = $this->normalizePaths($paths);

        if (! empty($paths)) {
            return $paths;
        }

        $configured = $this->normalizePaths($this->defaultPaths);

        if (! empty($configured)) {
            return $configured;
        }

        return $this->normalizePaths($this->fallbackPaths());
    }

    /**
     * @param  array<int, string>  $paths
     * @return array<int, string>
     */
    private function normalizePaths(array $paths): array
    {
        $filtered = array_filter($paths, static fn (string $path): bool => is_dir($path));

        return array_values(array_unique($filtered));
    }

    /**
     * @return array<int, string>
     */
    private function fallbackPaths(): array
    {
        if (! function_exists('base_path')) {
            return [];
        }

        return array_filter([
            base_path('app'),
            base_path('resources'),
        ], static fn ($path) => is_dir($path));
    }

    private function removeKeyFromFile(string $file, string $key, ?string $group): bool
    {
        $loader = $this->repository->getLoaderFor($file);

        if (! $loader) {
            return false;
        }

        return $loader->remove($file, $key, $group);
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

    private function countEntries(array $unusedKeys): int
    {
        $count = 0;

        foreach ($unusedKeys as $locales) {
            $count += count($locales);
        }

        return $count;
    }
}
