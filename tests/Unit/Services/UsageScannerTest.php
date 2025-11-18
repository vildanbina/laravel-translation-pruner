<?php

declare(strict_types=1);

use Illuminate\Config\Repository as BaseConfigRepository;
use VildanBina\TranslationPruner\Contracts\ScannerInterface;
use VildanBina\TranslationPruner\Services\UsageScanner;

final class ContentStubScanner implements ScannerInterface
{
    public function canHandle(string $fileName): bool
    {
        return str_ends_with($fileName, '.stub');
    }

    public function scan(string $content): array
    {
        return array_filter(array_map('trim', explode(',', $content)));
    }
}

final class StaticFirstStubScanner implements ScannerInterface
{
    public function canHandle(string $fileName): bool
    {
        return str_ends_with($fileName, '.stub');
    }

    public function scan(string $content): array
    {
        return ['messages.first'];
    }
}

final class StaticSecondStubScanner implements ScannerInterface
{
    public function canHandle(string $fileName): bool
    {
        return str_ends_with($fileName, '.stub');
    }

    public function scan(string $content): array
    {
        return ['messages.second'];
    }
}

beforeEach(function () {
    /** @var VildanBina\TranslationPruner\Tests\TestCase $this */
    $this->config = new BaseConfigRepository(['translation-pruner' => []]);
    $this->tempDir = sys_get_temp_dir().'/usage-scanner-test-'.uniqid();
    mkdir($this->tempDir, 0777, true);
});

afterEach(function () {
    /** @var VildanBina\TranslationPruner\Tests\TestCase $this */
    if (isset($this->tempDir) && is_dir($this->tempDir)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->tempDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
        }

        rmdir($this->tempDir);
    }
});

it('returns unique translation keys from matching files', function () {
    /** @var VildanBina\TranslationPruner\Tests\TestCase $this */
    $included = $this->tempDir.'/included';
    $excluded = $this->tempDir.'/excluded';
    mkdir($included);
    mkdir($excluded);

    file_put_contents($included.'/one.stub', 'messages.welcome,auth.login');
    file_put_contents($included.'/two.stub', 'auth.login,shared.title');
    file_put_contents($excluded.'/skip.stub', 'messages.hidden');

    $this->config->set('translation-pruner', [
        'scanners' => [ContentStubScanner::class],
        'ignore' => ['excluded'],
        'file_patterns' => ['*.stub'],
    ]);

    $usageScanner = new UsageScanner(config: $this->config);

    $keys = $usageScanner->scan([$this->tempDir]);

    expect($keys)->toEqualCanonicalizing(['messages.welcome', 'auth.login', 'shared.title'])
        ->and($keys)->not->toContain('messages.hidden');
});

it('ignores non-existent directories', function () {
    /** @var VildanBina\TranslationPruner\Tests\TestCase $this */
    $this->config->set('translation-pruner', [
        'scanners' => [ContentStubScanner::class],
        'file_patterns' => ['*.php'],
    ]);

    $usageScanner = new UsageScanner(config: $this->config);

    expect($usageScanner->scan(['/path/that/does/not/exist']))->toBe([]);
});

it('runs all scanners that support a file', function () {
    /** @var VildanBina\TranslationPruner\Tests\TestCase $this */
    $file = $this->tempDir.'/shared.stub';
    file_put_contents($file, 'content');

    $this->config->set('translation-pruner', [
        'scanners' => [StaticFirstStubScanner::class, StaticSecondStubScanner::class],
        'file_patterns' => ['*.stub'],
    ]);

    $usageScanner = new UsageScanner(config: $this->config);

    $keys = $usageScanner->scan([$this->tempDir]);

    expect($keys)->toEqualCanonicalizing(['messages.first', 'messages.second']);
});
