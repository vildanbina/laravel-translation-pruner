<?php

namespace VildanBina\TranslationPruner\Scanners;

class BladeScanner
{
    public function canHandle(string $filename): bool
    {
        // BladeScanner is handled specially in TranslationPruner
        // This method checks for blade.php in filename
        return str_ends_with($filename, '.blade.php');
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
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                $keys = array_merge($keys, $matches[1]);
            }
        }

        return array_unique($keys);
    }
}
