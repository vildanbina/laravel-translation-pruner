<?php

declare(strict_types=1);

namespace VildanBina\TranslationPruner;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use VildanBina\TranslationPruner\Commands\PruneCommand;
use VildanBina\TranslationPruner\Commands\ScanCommand;

class TranslationPrunerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-translation-pruner')
            ->hasConfigFile('translation-pruner')
            ->hasCommands([
                ScanCommand::class,
                PruneCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(TranslationPruner::class, function ($app) {
            $excludePatterns = config('translation-pruner.exclude', [
                'validation.*',
                'auth.*',
                'pagination.*',
                'passwords.*',
            ]);

            return new TranslationPruner($excludePatterns);
        });
    }
}
