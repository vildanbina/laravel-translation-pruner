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
        $content = StringLiteralScanner::withoutComments($content);
        $keys = [];

        // Vue/JS translation patterns
        $patterns = [
            '/trans\s*\(\s*[\'"]([^\'"]+)[\'"]/',
            '/\$t\s*\(\s*[\'"]([^\'"]+)[\'"]/',
            '/i18n\.t\s*\(\s*[\'"]([^\'"]+)[\'"]/',
            '/v-t=[\'"]([^\'"]+)[\'"]/',
            '/\.t\s*\(\s*[\'"]([^\'"]+)[\'"]/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                $keys = array_merge($keys, $matches[1]);
            }
        }

        return array_values(array_unique($keys));
    }
}
