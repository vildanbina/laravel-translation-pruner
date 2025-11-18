<?php

declare(strict_types=1);

namespace VildanBina\TranslationPruner\Contracts;

interface LoaderInterface
{
    /**
     * Determine if this loader can handle the given file.
     */
    public function canHandle(string $file): bool;

    /**
     * Load translations from the given file.
     *
     * @return array<int|string, mixed>
     */
    public function load(string $file): array;

    /**
     * Save translations to the given file.
     *
     * @param  array<int|string, mixed>  $translations
     */
    public function save(string $file, array $translations): bool;

    /**
     * Remove a translation key from the given file.
     */
    public function remove(string $file, string $key, ?string $group = null): bool;
}
