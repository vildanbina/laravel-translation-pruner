<?php

declare(strict_types=1);

namespace VildanBina\TranslationPruner\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use VildanBina\TranslationPruner\TranslationPruner;

class PruneCommand extends Command
{
    protected $signature = 'translation:prune
        {--dry-run : Show what would be deleted without actually doing it}
        {--force : Skip confirmation when pruning}
        {--path=* : Limit scanning to specific path(s)}';

    protected $description = 'Remove unused translations';

    /**
     * Create a new console command instance.
     */
    public function __construct(private readonly Repository $repository)
    {
        parent::__construct();
    }

    public function handle(TranslationPruner $pruner): int
    {
        $this->info('Finding unused translations...');

        $paths = $this->resolvePathsOption();
        /** @var array{
         *     total:int,
         *     used:int,
         *     unused:int,
         *     unused_keys: array<string, array<string, array<string, mixed>>>
         * } $results
         */
        $results = $pruner->scan($paths);

        if (empty($results['unused_keys'])) {
            $this->info('✅ No unused translations to remove!');

            return 0;
        }

        $this->displayUnusedSummary($results['unused_keys']);

        if ($this->option('dry-run')) {
            $this->info("\n🔍 DRY RUN MODE - No files will be modified");

            return 0;
        }

        if (! $this->option('force') && ! $this->confirm('Delete these unused translations?')) {
            $this->info('Operation cancelled.');

            return 0;
        }

        $deleted = $pruner->prune($results['unused_keys'], dryRun: false);

        $this->info("✅ Deleted {$deleted} unused translation entries");

        return 0;
    }

    /**
     * @return array<int, string>
     */
    private function resolvePathsOption(): array
    {
        $rawPaths = $this->option('path');
        $rawValues = is_array($rawPaths)
            ? $rawPaths
            : (is_string($rawPaths) && $rawPaths !== '' ? [$rawPaths] : []);

        /** @var array<int, string> $paths */
        $paths = array_values(array_filter(
            $rawValues,
            static fn ($path): bool => is_string($path) && $path !== ''
        ));

        if (! empty($paths)) {
            return $paths;
        }

        $configured = $this->repository->get('translation-pruner.paths', []);

        if (! is_array($configured)) {
            return [];
        }

        /** @var array<int, string> $configuredPaths */
        $configuredPaths = array_values(array_filter(
            $configured,
            static fn ($path): bool => is_string($path) && $path !== ''
        ));

        return $configuredPaths;
    }

    /**
     * @param  array<string, array<string, array<string, mixed>>>  $unusedKeys
     */
    private function displayUnusedSummary(array $unusedKeys): void
    {
        $totalEntries = array_sum(array_map(
            static fn (array $locales): int => count($locales),
            $unusedKeys
        ));
        $this->info("Found {$totalEntries} unused translation entries:");

        foreach ($unusedKeys as $key => $locales) {
            $localesList = implode(', ', array_keys($locales));
            $this->line(sprintf('  • %s (%s)', $key, $localesList));
        }
    }
}
