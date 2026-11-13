<?php

declare(strict_types=1);

namespace VildanBina\TranslationPruner\Scanners;

use RuntimeException;
use VildanBina\TranslationPruner\Contracts\ScannerInterface;

class StringLiteralScanner implements ScannerInterface
{
    private const string TOKENS = <<<'REGEX'
    ~
    (?<comment>//[^\r\n]*|/\*[\s\S]*?\*/|<!--[\s\S]*?-->|\{\{--[\s\S]*?--\}\})
    |(?<regexp>(?:(?<=[=(:,;!&|?{}])|(?<=\breturn)|(?<=\bthrow)|(?<=\byield)|^)
        \s*/(?![/*])(?:\[(?:\\[\s\S]|[^\]\\]++)*+\]|\\[\s\S]|[^/\\\[\r\n]++)*+/[a-z]*)
    |(?<literal>'(?:[^'\\]++|\\[\s\S])*+'|"(?:[^"\\]++|\\[\s\S])*+"|`(?:[^`\\]++|\\[\s\S])*+`)
    ~umx
    REGEX;

    public static function withoutComments(string $content): string
    {
        if (str_contains($content, '<?php')) {
            $tokens = token_get_all($content);
            $content = '';

            foreach ($tokens as $token) {
                $content .= is_array($token)
                    ? (in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true) ? ' ' : $token[1])
                    : $token;
            }
        }

        $result = preg_replace_callback(self::TOKENS, static function (array $match): string {
            return ($match['comment'] ?? '') !== '' || ($match['regexp'] ?? '') !== '' ? ' ' : $match[0];
        }, $content);

        if ($result === null) {
            throw new RuntimeException('Unable to read translation source: '.preg_last_error_msg());
        }

        return $result;
    }

    public function canHandle(string $fileName): bool
    {
        return in_array(pathinfo($fileName, PATHINFO_EXTENSION), ['php', 'vue', 'js', 'jsx', 'ts', 'tsx'], true);
    }

    public function scan(string $content): array
    {
        $content = self::withoutComments($content);

        if (preg_match_all(self::TOKENS, $content, $matches, PREG_SET_ORDER) === false) {
            throw new RuntimeException('Unable to scan translation string literals: '.preg_last_error_msg());
        }

        $keys = [];

        foreach ($matches as $match) {
            if (($match['literal'] ?? '') === '') {
                continue;
            }

            $key = mb_substr($match['literal'], 1, -1);
            $quote = $match['literal'][0];
            $key = preg_replace_callback('~\\\\(.)~s', static fn (array $escape): string => $escape[1] === $quote || $escape[1] === '\\'
                ? $escape[1]
                : $escape[0], $key) ?? $key;

            // Keep potential references for indirect calls; the pruner intersects them with real keys.
            $keys[$key] = true;
        }

        return array_map('strval', array_keys($keys));
    }
}
