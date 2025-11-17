<?php

declare(strict_types=1);

namespace VildanBina\TranslationPruner\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use VildanBina\TranslationPruner\TranslationPrunerServiceProvider;

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
