<?php

declare(strict_types=1);

namespace VildanBina\TranslationPruner\Scanners;

use VildanBina\TranslationPruner\Contracts\ScannerInterface;

class VueScanner implements ScannerInterface
{
    public function canHandle(string $fileName): bool
    {
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);

        return in_array($extension, ['vue', 'js', 'ts', 'jsx', 'tsx']);
    }

    public function scan(string $content): array
    {
        $keys = [];

        // Vue/JS translation patterns
        $patterns = [
            '/trans\([\'"]([^\'"]+)[\'"]/',
            '/\$t\([\'"]([^\'"]+)[\'"]/',
            '/i18n\.t\([\'"]([^\'"]+)[\'"]/',
            '/v-t=[\'"]([^\'"]+)[\'"]/',
            '/\.t\([\'"]([^\'"]+)[\'"]/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                $keys = array_merge($keys, $matches[1]);
            }
        }

        return array_unique($keys);
    }
}
