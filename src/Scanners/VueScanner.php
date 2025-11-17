<?php

namespace VildanBina\TranslationPruner\Scanners;

class VueScanner
{
    public function canHandle(string $extension): bool
    {
        return in_array($extension, ['vue', 'js', 'ts', 'jsx', 'tsx']);
    }

    public function scan(string $content): array
    {
        $keys = [];

        // Vue/JS translation patterns
        $patterns = [
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
