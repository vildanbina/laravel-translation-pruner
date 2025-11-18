<?php

declare(strict_types=1);

namespace VildanBina\TranslationPruner\Tests\Support;

use VildanBina\TranslationPruner\Tests\TestCase;

final class TempLangPathStore
{
    /**
     * @var array<int, array{original: string, temp: string}>
     */
    private static array $paths = [];

    public static function remember(TestCase $test, string $original, string $temp): void
    {
        self::$paths[spl_object_id($test)] = [
            'original' => $original,
            'temp' => $temp,
        ];
    }

    /**
     * @return array{original: string, temp: string}|null
     */
    public static function pull(TestCase $test): ?array
    {
        $id = spl_object_id($test);
        $state = self::$paths[$id] ?? null;
        unset(self::$paths[$id]);

        return $state;
    }
}
