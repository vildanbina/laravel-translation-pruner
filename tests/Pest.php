<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Illuminate\Testing\PendingCommand;
use VildanBina\TranslationPruner\Tests\TestCase;

use function Pest\Laravel\artisan;

uses(TestCase::class)->in('Feature', 'Unit');

/**
 * @param  array<string, mixed>  $parameters
 */
function runArtisan(string $command, array $parameters = []): PendingCommand
{
    $result = artisan($command, $parameters);

    if (! $result instanceof PendingCommand) {
        throw new RuntimeException("Command [{$command}] did not return a PendingCommand.");
    }

    return $result;
}

function fixturesPath(string $path = ''): string
{
    return __DIR__.'/fixtures/'.mb_ltrim($path, '/');
}

function useTemporaryLangPath(TestCase $test): void
{
    $test->originalLangPath = lang_path();
    $test->tempLangPath = sys_get_temp_dir().'/translation-pruner-lang-'.uniqid('', true);

    app()->useLangPath($test->tempLangPath);
    config()->set('translation-pruner.lang_path', $test->tempLangPath);

    if (! is_dir($test->tempLangPath)) {
        mkdir($test->tempLangPath, 0755, true);
    }
}

function restoreTemporaryLangPath(TestCase $test): void
{
    /** @var Filesystem $filesystem */
    $filesystem = app(Filesystem::class);

    if ($test->tempLangPath !== '' && is_dir($test->tempLangPath)) {
        $filesystem->deleteDirectory($test->tempLangPath);
    }

    if ($test->originalLangPath !== '') {
        app()->useLangPath($test->originalLangPath);
        config()->set('translation-pruner.lang_path', $test->originalLangPath);
    }

    $test->tempLangPath = '';
    $test->originalLangPath = '';
}
