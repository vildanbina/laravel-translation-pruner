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
        $keys = [];

        // Blade translation patterns
        $patterns = [
            '/@lang\([\'"]([^\'"]+)[\'"]\)/',
            '/@choice\([\'"]([^\'"]+)[\'"]/',
            '/\{\{\s*__\([\'"]([^\'"]+)[\'"]/',
            '/\{\{\s*trans\([\'"]([^\'"]+)[\'"]/',
            '/__\([\'"]([^\'"]+)[\'"]\)/',
            '/trans\([\'"]([^\'"]+)[\'"]/',
            '/trans_choice\([\'"]([^\'"]+)[\'"]/',
            '/Lang::get\([\'"]([^\'"]+)[\'"]/',
            '/Lang::choice\([\'"]([^\'"]+)[\'"]/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                $keys = array_merge($keys, $matches[1]);
            }
        }

        return array_unique($keys);
    }
}
