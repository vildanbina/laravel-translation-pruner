<?php

declare(strict_types=1);

namespace VildanBina\TranslationPruner\Tests\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Testing\PendingCommand;
use RuntimeException;
use VildanBina\TranslationPruner\Tests\TestCase;

use function Pest\Laravel\artisan;

final class TestHelpers
{
    /**
     * @param  array<string, mixed>  $parameters
     */
    public static function runArtisan(string $command, array $parameters = []): PendingCommand
    {
        $result = artisan($command, $parameters);

        throw_unless($result instanceof PendingCommand, RuntimeException::class, "Command [{$command}] did not return a PendingCommand.");

        return $result;
    }

    public static function fixturesPath(string $path = ''): string
    {
        return __DIR__.'/../fixtures/'.mb_ltrim($path, '/');
    }

    public static function useTemporaryLangPath(TestCase $test): void
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

    public static function restoreTemporaryLangPath(TestCase $test): void
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
}
