<?php

declare(strict_types=1);

namespace VildanBina\TranslationPruner\Tests;

use AllowDynamicProperties;
use Illuminate\Config\Repository as BaseConfigRepository;
use Illuminate\Filesystem\Filesystem;
use Orchestra\Testbench\TestCase as Orchestra;
use VildanBina\TranslationPruner\Services\TranslationRepository;
use VildanBina\TranslationPruner\TranslationPrunerServiceProvider;

/**
 * @property string $tempDir
 * @property string $testFile
 * @property Filesystem $filesystem
 * @property string $langPath
 * @property BaseConfigRepository $config
 * @property TranslationRepository $repository
 */
#[AllowDynamicProperties]
class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            TranslationPrunerServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app->make('config')->set('translation-pruner.paths', [
            $this->getTestPath('app'),
            $this->getTestPath('resources'),
        ]);

        $app->make('config')->set('translation-pruner.exclude', [
            'validation.*',
            'auth.*',
        ]);

        $app->make('config')->set('app.locale', 'en');
    }

    protected function getTestPath(string $path = ''): string
    {
        return __DIR__.'/fixtures/'.mb_ltrim($path, '/');
    }
}
