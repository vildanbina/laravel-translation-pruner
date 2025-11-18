<?php

declare(strict_types=1);

namespace VildanBina\TranslationPruner;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use VildanBina\TranslationPruner\Commands\PruneCommand;
use VildanBina\TranslationPruner\Services\TranslationRepository;
use VildanBina\TranslationPruner\Services\UsageScanner;

class TranslationPrunerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-translation-pruner')
            ->hasConfigFile('translation-pruner')
            ->hasCommands([
                PruneCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(TranslationRepository::class, function (Application $app): TranslationRepository {
            /** @var ConfigRepository $config */
            $config = $app->make('config');
            /** @var Filesystem $filesystem */
            $filesystem = $app->make('files');

            return new TranslationRepository($config, $filesystem);
        });

        $this->app->singleton(UsageScanner::class, function (Application $app): UsageScanner {
            /** @var ConfigRepository $config */
            $config = $app->make('config');

            return new UsageScanner($config);
        });

        $this->app->singleton(TranslationPruner::class, function (Application $app): TranslationPruner {
            /** @var ConfigRepository $config */
            $config = $app->make('config');

            return new TranslationPruner(
                config: $config,
                repository: $app->make(TranslationRepository::class),
                usageScanner: $app->make(UsageScanner::class),
            );
        });
    }
}
