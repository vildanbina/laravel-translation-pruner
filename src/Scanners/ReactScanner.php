<?php

declare(strict_types=1);

namespace VildanBina\TranslationPruner\Scanners;

use VildanBina\TranslationPruner\Contracts\ScannerInterface;

class ReactScanner implements ScannerInterface
{
    public function canHandle(string $fileName): bool
    {
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);

        return in_array($extension, ['js', 'jsx', 'ts', 'tsx'], true);
    }

    public function scan(string $content): array
    {
        $content = StringLiteralScanner::withoutComments($content);
        $keys = [];

        $patterns = [
            '/\bt\s*\(\s*[\'"]([^\'"]+)[\'"]/i',
            '/i18n\.t\s*\(\s*[\'"]([^\'"]+)[\'"]/',
            '/<Trans[^>]+i18nKey=[\'"]([^\'"]+)[\'"]/',
            '/<FormattedMessage[^>]+id=[\'"]([^\'"]+)[\'"]/',
            '/\bt\s*\(\s*\`([^`]+)\`/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                $keys = array_merge($keys, $matches[1]);
            }
        }

        return array_values(array_unique($keys));
    }
}
