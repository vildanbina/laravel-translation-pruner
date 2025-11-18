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
        /** @var array<string, mixed> $settings */
        $settings = (array) $config->get('translation-pruner', []);

        $this->repository = $repository;
        $this->usageScanner = $usageScanner;
        $this->excludePatterns = $this->normalizeStringList(
            $settings['exclude'] ?? null,
            [
                'validation.*',
                'auth.*',
                'pagination.*',
                'passwords.*',
            ]
        );
        $this->defaultPaths = $this->normalizeStringList($settings['paths'] ?? null, []);
    }

    /**
     * @param  array<int, string>  $paths
     * @return array{
     *     total: int,
     *     used: int,
     *     unused: int,
     *     unused_keys: array<string, array<string, array<string, mixed>>>
     * }
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

    /**
     * @param  array<string, array<string, array<string, mixed>>>  $unusedKeys
     */
    public function prune(array $unusedKeys, bool $dryRun = true): int
    {
        if ($dryRun) {
            return $this->countEntries($unusedKeys);
        }

        $deleted = 0;

        foreach ($unusedKeys as $key => $locales) {
            foreach ($locales as $info) {
                $file = $info['file'] ?? null;

                if (! is_string($file)) {
                    continue;
                }

                $targetKey = is_string($info['key_path'] ?? null)
                    ? $info['key_path']
                    : $key;

                $group = $info['group'] ?? null;
                $group = is_string($group) && $group !== '' ? $group : null;

                if ($this->removeKeyFromFile($file, $targetKey, $group)) {
                    $deleted++;
                }
            }
        }

        return $deleted;
    }

    /**
     * @return array<int, string>|string|null
     */
    protected function resolveCustomFallbackPaths(): array|string|null
    {
        return null;
    }

    protected function resolveBasePathRoot(): ?string
    {
        return function_exists('base_path') ? base_path() : null;
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
        $paths = $this->resolveCustomFallbackPaths();

        if ($paths !== null) {
            return $this->sanitizeFallbackPaths($paths);
        }

        $basePath = $this->resolveBasePathRoot();

        if ($basePath === null || $basePath === '') {
            return [];
        }

        return $this->sanitizeFallbackPaths([
            $basePath.'/app',
            $basePath.'/resources',
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function sanitizeFallbackPaths(mixed $paths): array
    {
        if (! is_array($paths)) {
            $paths = is_string($paths) && $paths !== '' ? [$paths] : [];
        }

        return array_values(array_filter(
            $paths,
            static fn ($path): bool => is_string($path) && $path !== ''
        ));
    }

    private function removeKeyFromFile(string $file, string $key, ?string $group): bool
    {
        $loader = $this->repository->getLoaderFor($file);

        if (! $loader) {
            return false;
        }

        return $loader->remove($file, $key, $group);
    }

    /**
     * @param  array<string, array<string, array<string, mixed>>>  $unused
     * @return array<string, array<string, array<string, mixed>>>
     */
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

    /**
     * @param  array<string, array<string, array<string, mixed>>>  $unusedKeys
     */
    private function countEntries(array $unusedKeys): int
    {
        $count = 0;

        foreach ($unusedKeys as $locales) {
            $count += count($locales);
        }

        return $count;
    }

    /**
     * @param  array<int, string>  $default
     * @return array<int, string>
     */
    private function normalizeStringList(mixed $values, array $default): array
    {
        if (! is_array($values)) {
            return $default;
        }

        return array_values(array_filter(
            $values,
            static fn ($value): bool => is_string($value) && $value !== ''
        ));
    }
}
