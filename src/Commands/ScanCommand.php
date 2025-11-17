<?php

declare(strict_types=1);

namespace VildanBina\TranslationPruner\Commands;

use Illuminate\Console\Command;
use VildanBina\TranslationPruner\TranslationPruner;

class ScanCommand extends Command
{
    protected $signature = 'translation:scan
        {--dry-run : Show what would be deleted without actually doing it}
        {--force : Skip confirmation when pruning}
        {--save= : Save results to a file}';

    protected $description = 'Find unused translations';

    public function handle(TranslationPruner $pruner): int
    {
        $this->info('Scanning for translations...');

        $paths = config('translation-pruner.paths', []);
        $results = $pruner->scan($paths);

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total translations', $results['total']],
                ['Used translations', $results['used']],
                ['Unused translations', $results['unused']],
            ]
        );

        if (empty($results['unused_keys'])) {
            $this->info('✅ No unused translations found!');

            if ($this->option('save')) {
                file_put_contents(
                    $this->option('save'),
                    json_encode($results, JSON_PRETTY_PRINT)
                );
                $this->info("Results saved to: {$this->option('save')}");
            }

            return 0;
        }

        $this->info("\nUnused translations:");
        foreach ($results['unused_keys'] as $key => $locales) {
            $this->line("  • {$key}");
        }

        if ($this->option('save')) {
            file_put_contents(
                $this->option('save'),
                json_encode($results, JSON_PRETTY_PRINT)
            );
            $this->info("Results saved to: {$this->option('save')}");
        }

        return 0;
    }
}
