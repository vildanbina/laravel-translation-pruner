<?php

declare(strict_types=1);

namespace VildanBina\TranslationPruner\Contracts;

interface ScannerInterface
{
    /**
     * Determine if this scanner can handle the given file.
     */
    public function canHandle(string $fileName): bool;

    /**
     * Scan the given content for translation keys.
     *
     * @return array<string>
     */
    public function scan(string $content): array;
}
