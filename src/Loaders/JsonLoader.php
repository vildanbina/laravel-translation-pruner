<?php

declare(strict_types=1);

namespace VildanBina\TranslationPruner\Loaders;

use VildanBina\TranslationPruner\Contracts\LoaderInterface;

class JsonLoader implements LoaderInterface
{
    public function canHandle(string $file): bool
    {
        return pathinfo($file, PATHINFO_EXTENSION) === 'json';
    }

    /**
     * @return array<int|string, mixed>
     */
    public function load(string $file): array
    {
        if (! file_exists($file)) {
            return [];
        }

        $content = file_get_contents($file);
        if ($content === false) {
            return [];
        }

        $translations = json_decode($content, true);

        if (! is_array($translations)) {
            return [];
        }

        /** @var array<string, mixed> $translations */
        return $translations;
    }

    public function remove(string $file, string $key, ?string $group = null): bool
    {
        $translations = $this->load($file);

        if (! isset($translations[$key])) {
            return false;
        }

        unset($translations[$key]);

        if (empty($translations)) {
            return unlink($file);
        }

        return $this->save($file, $translations);
    }

    /**
     * @param  array<int|string, mixed>  $translations
     */
    public function save(string $file, array $translations): bool
    {
        $content = json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if ($content === false) {
            return false;
        }

        return file_put_contents($file, $content) !== false;
    }
}
