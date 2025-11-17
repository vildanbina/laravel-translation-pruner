<?php

declare(strict_types=1);

namespace VildanBina\TranslationPruner;

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
        $this->app->singleton(TranslationRepository::class, fn ($app) => new TranslationRepository(
            config: $app['config'],
            filesystem: $app['files'],
        ));

        $this->app->singleton(UsageScanner::class, fn ($app) => new UsageScanner($app['config']));

        $this->app->singleton(TranslationPruner::class, fn ($app) => new TranslationPruner(
            config: $app['config'],
            repository: $app->make(TranslationRepository::class),
            usageScanner: $app->make(UsageScanner::class),
        ));
    }
}
