<?php

declare(strict_types=1);

namespace VildanBina\TranslationPruner\Scanners;

use VildanBina\TranslationPruner\Contracts\ScannerInterface;

class PhpScanner implements ScannerInterface
{
    public function canHandle(string $fileName): bool
    {
        return pathinfo($fileName, PATHINFO_EXTENSION) === 'php'
            && ! str_ends_with($fileName, '.blade.php');
    }

    public function scan(string $content): array
    {
        $keys = [];

        // Basic patterns for common Laravel translation functions
        $patterns = [
            '/__\([\'"]([^\'"]+)[\'"]/',
            '/trans\([\'"]([^\'"]+)[\'"]/',
            '/Lang::get\([\'"]([^\'"]+)[\'"]/',
            '/trans_choice\([\'"]([^\'"]+)[\'"]/',
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
