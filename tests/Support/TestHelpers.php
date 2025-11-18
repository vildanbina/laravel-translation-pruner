<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Illuminate\Testing\PendingCommand;
use VildanBina\TranslationPruner\Tests\TestCase;

use function Pest\Laravel\artisan;

/**
 * @param  array<string, mixed>  $parameters
 */
function runArtisan(string $command, array $parameters = []): PendingCommand
{
    $result = artisan($command, $parameters);

    throw_unless($result instanceof PendingCommand, RuntimeException::class, "Command [{$command}] did not return a PendingCommand.");

    return $result;
}

function fixturesPath(string $path = ''): string
{
    return __DIR__.'/../fixtures/'.mb_ltrim($path, '/');
}

function useTemporaryLangPath(TestCase $test): void
{
    $original = lang_path();
    $temp = sys_get_temp_dir().'/translation-pruner-lang-'.uniqid('', true);

    TempLangPathStore::remember($test, $original, $temp);

    app()->useLangPath($temp);
    config()->set('translation-pruner.lang_path', $temp);

    if (! is_dir($temp)) {
        mkdir($temp, 0755, true);
    }
}

function restoreTemporaryLangPath(TestCase $test): void
{
    $state = TempLangPathStore::pull($test);

    if ($state === null) {
        return;
    }

    /** @var Filesystem $filesystem */
    $filesystem = app(Filesystem::class);

    if (is_dir($state['temp'])) {
        $filesystem->deleteDirectory($state['temp']);
    }

    app()->useLangPath($state['original']);
    config()->set('translation-pruner.lang_path', $state['original']);
}

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
