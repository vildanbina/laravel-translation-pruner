<?php

declare(strict_types=1);

namespace VildanBina\TranslationPruner\Commands;

use Illuminate\Console\Command;
use VildanBina\TranslationPruner\TranslationPruner;

class PruneCommand extends Command
{
    protected $signature = 'translation:prune
        {--dry-run : Show what would be deleted without actually doing it}
        {--force : Skip confirmation when pruning}
        {--path=* : Limit scanning to specific path(s)}';

    protected $description = 'Remove unused translations';

    public function handle(TranslationPruner $pruner): int
    {
        $this->info('Finding unused translations...');

        $paths = $this->resolvePathsOption();
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
        $paths = array_filter((array) $this->option('path'));

        if (! empty($paths)) {
            return $paths;
        }

        return config('translation-pruner.paths', []);
    }

    private function displayUnusedSummary(array $unusedKeys): void
    {
        $totalEntries = array_sum(array_map('count', $unusedKeys));
        $this->info("Found {$totalEntries} unused translation entries:");

        foreach ($unusedKeys as $key => $locales) {
            $localesList = implode(', ', array_keys($locales));
            $this->line(sprintf('  • %s (%s)', $key, $localesList));
        }
    }
}
