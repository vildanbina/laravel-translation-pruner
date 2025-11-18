<?php

declare(strict_types=1);

namespace VildanBina\TranslationPruner\Services;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use VildanBina\TranslationPruner\Contracts\LoaderInterface;
use VildanBina\TranslationPruner\Loaders\JsonLoader;
use VildanBina\TranslationPruner\Loaders\PhpArrayLoader;

class TranslationRepository
{
    /**
     * @var array<int, LoaderInterface>
     */
    private array $loaders = [];

    private string $langPath;

    public function __construct(
        ConfigRepository $config,
        protected Filesystem $filesystem,
    ) {
        /** @var array<string, mixed> $settings */
        $settings = (array) $config->get('translation-pruner', []);

        $this->loaders = $this->instantiateLoaders($settings['loaders'] ?? null);
        $this->langPath = is_string($settings['lang_path'] ?? null)
            ? $settings['lang_path']
            : $this->defaultLangPath();
    }

    /**
     * @return array<string, array<string, array<string, mixed>>>
     */
    public function all(): array
    {
        /** @var array<string, array<string, array<string, mixed>>> $available */
        $available = [];

        if (! $this->filesystem->isDirectory($this->langPath)) {
            return $available;
        }

        foreach ($this->filesystem->glob($this->langPath.'/*.json') ?: [] as $file) {
            if (! is_string($file)) {
                continue;
            }

            $this->hydrateJsonTranslations($available, $file);
        }

        foreach ($this->filesystem->directories($this->langPath) as $localeDirectory) {
            if (! is_string($localeDirectory)) {
                continue;
            }

            $this->hydratePhpTranslations($available, $localeDirectory);
        }

        ksort($available);

        return $available;
    }

    public function getLoaderFor(string $file): ?LoaderInterface
    {
        foreach ($this->loaders as $loader) {
            if ($loader->canHandle($file)) {
                return $loader;
            }
        }

        return null;
    }

    public function getLangPath(): string
    {
        return $this->langPath;
    }

    /**
     * @param  array<string, array<string, array<string, mixed>>>  $available
     */
    private function hydrateJsonTranslations(array &$available, string $file): void
    {
        $loader = $this->getLoaderFor($file);

        if (! $loader) {
            return;
        }

        $locale = basename($file, '.json');
        $translations = $loader->load($file);

        foreach ($translations as $key => $value) {
            $normalizedKey = (string) $key;

            $available[$normalizedKey][$locale] = [
                'file' => $file,
                'group' => null,
                'locale' => $locale,
                'value' => $value,
            ];
        }
    }

    /**
     * @param  array<string, array<string, array<string, mixed>>>  $available
     */
    private function hydratePhpTranslations(array &$available, string $localeDirectory): void
    {
        $locale = basename($localeDirectory);

        foreach ($this->filesystem->glob($localeDirectory.'/*.php') ?: [] as $file) {
            if (! is_string($file)) {
                continue;
            }

            $loader = $this->getLoaderFor($file);

            if (! $loader) {
                continue;
            }

            $translations = $loader->load($file);

            $group = basename($file, '.php');

            /** @var array<string, mixed> $flattened */
            $flattened = Arr::dot($translations);

            foreach ($flattened as $key => $value) {
                $fullKey = sprintf('%s.%s', $group, $key);

                $available[$fullKey][$locale] = [
                    'file' => $file,
                    'group' => $group,
                    'key_path' => $key,
                    'locale' => $locale,
                    'value' => $value,
                ];
            }
        }
    }

    private function defaultLangPath(): string
    {
        if (function_exists('base_path')) {
            return base_path('lang');
        }

        $cwd = getcwd();

        if ($cwd === false) {
            return 'lang';
        }

        return $cwd.'/lang';
    }

    /**
     * @return array<int, LoaderInterface>
     */
    private function instantiateLoaders(mixed $classes): array
    {
        if (! is_array($classes) || empty($classes)) {
            return $this->defaultLoaders();
        }

        $validClasses = array_values(array_filter(
            $classes,
            static fn ($class): bool => is_string($class)
                && $class !== ''
                && class_exists($class)
                && is_subclass_of($class, LoaderInterface::class)
        ));

        if (empty($validClasses)) {
            return $this->defaultLoaders();
        }

        return array_map(
            static fn (string $class): LoaderInterface => new $class,
            $validClasses
        );
    }

    /**
     * @return array<int, LoaderInterface>
     */
    private function defaultLoaders(): array
    {
        return [
            new JsonLoader,
            new PhpArrayLoader,
        ];
    }
}
