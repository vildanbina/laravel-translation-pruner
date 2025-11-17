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

    public function load(string $file): array
    {
        if (! file_exists($file)) {
            return [];
        }

        $content = file_get_contents($file);
        $translations = json_decode($content, true);

        return is_array($translations) ? $translations : [];
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

    public function save(string $file, array $translations): bool
    {
        $content = json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return file_put_contents($file, $content) !== false;
    }
}
