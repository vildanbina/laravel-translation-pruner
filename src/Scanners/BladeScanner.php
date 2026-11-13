<?php

declare(strict_types=1);

namespace VildanBina\TranslationPruner\Scanners;

use VildanBina\TranslationPruner\Contracts\ScannerInterface;

class BladeScanner implements ScannerInterface
{
    public function canHandle(string $fileName): bool
    {
        return str_ends_with($fileName, '.blade.php');
    }

    public function scan(string $content): array
    {
        $content = StringLiteralScanner::withoutComments($content);
        $keys = [];

        // Blade translation patterns
        $patterns = [
            '/@lang\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/',
            '/@choice\s*\(\s*[\'"]([^\'"]+)[\'"]/',
            '/\{\{\s*__\s*\(\s*[\'"]([^\'"]+)[\'"]/',
            '/\{\{\s*trans\s*\(\s*[\'"]([^\'"]+)[\'"]/',
            '/__\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/',
            '/trans\s*\(\s*[\'"]([^\'"]+)[\'"]/',
            '/trans_choice\s*\(\s*[\'"]([^\'"]+)[\'"]/',
            '/Lang\s*::\s*get\s*\(\s*[\'"]([^\'"]+)[\'"]/',
            '/Lang\s*::\s*choice\s*\(\s*[\'"]([^\'"]+)[\'"]/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                $keys = array_merge($keys, $matches[1]);
            }
        }

        return array_values(array_unique($keys));
    }
}
