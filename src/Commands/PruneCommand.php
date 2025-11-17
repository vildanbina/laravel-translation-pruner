<?php

namespace VildanBina\TranslationPruner\Commands;

use Illuminate\Console\Command;
use VildanBina\TranslationPruner\TranslationPruner;

class PruneCommand extends Command
{
    protected $signature = 'translation:prune
        {--dry-run : Show what would be deleted without actually doing it}
        {--force : Skip confirmation when pruning}';

    protected $description = 'Remove unused translations';

    public function handle(TranslationPruner $pruner): int
    {
        $this->info('Finding unused translations...');

        $paths = config('translation-pruner.paths', []);
        $results = $pruner->scan($paths);

        if (empty($results['unused_keys'])) {
            $this->info('✅ No unused translations to remove!');
            return 0;
        }

        $this->info("Found {$results['unused']} unused translations:");
        foreach ($results['unused_keys'] as $key => $locales) {
            $this->line("  • {$key}");
        }

        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        // Default to dry run mode unless explicitly disabled
        $shouldRunDryRun = !$this->input->hasParameterOption('--dry-run') || $dryRun !== false;

        if ($shouldRunDryRun) {
            $this->info("\n🔍 DRY RUN MODE - No files will be modified");
            $this->info("To actually delete these translations, run with --dry-run=false");
            return 0;
        }

        if (!$force && !$this->confirm('Delete these unused translations?')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $deleted = $pruner->prune($results['unused_keys'], dryRun: false);

        $this->info("✅ Deleted {$deleted} unused translations");
        return 0;
    }
}
