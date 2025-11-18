<?php

declare(strict_types=1);

use VildanBina\TranslationPruner\Tests\Support\TestHelpers as Helpers;

beforeEach(function () {
    /** @var VildanBina\TranslationPruner\Tests\TestCase $this */
    Helpers::useTemporaryLangPath($this);
});

afterEach(function () {
    /** @var VildanBina\TranslationPruner\Tests\TestCase $this */
    Helpers::restoreTemporaryLangPath($this);
});

it('can run prune command', function () {
    Helpers::runArtisan('translation:prune')
        ->expectsOutput('Finding unused translations...')
        ->assertSuccessful();
});

it('shows no unused translations message', function () {
    Helpers::runArtisan('translation:prune')
        ->expectsOutput('✅ No unused translations to remove!')
        ->assertSuccessful();
});

it('runs in dry run mode with --dry-run flag', function () {
    // Create translation file with unused key
    $enDir = lang_path('en');
    if (! is_dir($enDir)) {
        mkdir($enDir, 0755, true);
    }
    file_put_contents($enDir.'/messages.php', "<?php\n\nreturn [\n    'welcome' => 'Welcome',\n    'unused' => 'Not used',\n];");

    $testDir = sys_get_temp_dir().'/translation-pruner-test-'.uniqid();
    mkdir($testDir, 0755, true);
    file_put_contents($testDir.'/test.php', "<?php echo __('messages.welcome');");

    config()->set('translation-pruner.paths', [$testDir]);

    Helpers::runArtisan('translation:prune', ['--dry-run' => true])
        ->expectsOutputToContain('🔍 DRY RUN MODE')
        ->assertSuccessful();

    // File should still have unused key
    $translations = include $enDir.'/messages.php';
    expect($translations)->toHaveKey('unused');

    // Cleanup
    unlink($testDir.'/test.php');
    rmdir($testDir);
});

it('asks for confirmation before deleting', function () {
    // Create translation file with unused key
    $enDir = lang_path('en');
    if (! is_dir($enDir)) {
        mkdir($enDir, 0755, true);
    }
    file_put_contents($enDir.'/messages.php', "<?php\n\nreturn ['unused' => 'Not used'];");

    Helpers::runArtisan('translation:prune')
        ->expectsConfirmation('Delete these unused translations?', 'no')
        ->expectsOutput('Operation cancelled.')
        ->assertSuccessful();
});

it('lists unused translations before pruning', function () {
    // Create translation file with unused key
    $enDir = lang_path('en');
    if (! is_dir($enDir)) {
        mkdir($enDir, 0755, true);
    }
    file_put_contents($enDir.'/messages.php', "<?php\n\nreturn ['unused' => 'Not used'];");

    Helpers::runArtisan('translation:prune')
        ->expectsOutputToContain('Found 1 unused translation entries:')
        ->expectsOutputToContain('• messages.unused (en)')
        ->expectsConfirmation('Delete these unused translations?', 'no')
        ->assertSuccessful();
});

it('can delete translations with force flag', function () {
    // Create translation file with unused key
    $enDir = lang_path('en');
    if (! is_dir($enDir)) {
        mkdir($enDir, 0755, true);
    }
    file_put_contents($enDir.'/messages.php', "<?php\n\nreturn [\n    'welcome' => 'Welcome',\n    'unused' => 'Not used',\n];");

    $testDir = sys_get_temp_dir().'/translation-pruner-test-'.uniqid();
    mkdir($testDir, 0755, true);
    file_put_contents($testDir.'/test.php', "<?php echo __('messages.welcome');");

    config()->set('translation-pruner.paths', [$testDir]);

    Helpers::runArtisan('translation:prune', ['--force' => true])
        ->expectsOutputToContain('✅ Deleted 1 unused translation entries')
        ->assertSuccessful();

    // Verify unused key was removed
    $translations = include $enDir.'/messages.php';
    expect($translations)->toHaveKey('welcome')
        ->and($translations)->not->toHaveKey('unused');

    // Cleanup
    unlink($testDir.'/test.php');
    rmdir($testDir);
});

it('honors custom path options', function () {
    $enDir = lang_path('en');
    if (! is_dir($enDir)) {
        mkdir($enDir, 0755, true);
    }

    file_put_contents($enDir.'/messages.php', "<?php\n\nreturn ['special' => 'Special'];");

    $configDir = sys_get_temp_dir().'/translation-pruner-config-'.uniqid();
    mkdir($configDir, 0755, true);
    file_put_contents($configDir.'/uses.php', "<?php echo __('messages.special');");

    $isolatedDir = sys_get_temp_dir().'/translation-pruner-isolated-'.uniqid();
    mkdir($isolatedDir, 0755, true);
    file_put_contents($isolatedDir.'/noop.php', "<?php echo 'noop';");

    config()->set('translation-pruner.paths', [$configDir]);

    Helpers::runArtisan('translation:prune', ['--path' => [$isolatedDir]])
        ->expectsOutputToContain('messages.special')
        ->expectsConfirmation('Delete these unused translations?', 'no')
        ->assertSuccessful();

    unlink($configDir.'/uses.php');
    rmdir($configDir);
    unlink($isolatedDir.'/noop.php');
    rmdir($isolatedDir);

    config()->set('translation-pruner.paths', [
        Helpers::fixturesPath('app'),
        Helpers::fixturesPath('resources'),
    ]);
});

it('accepts single string path option and invalid configured paths', function () {
    $enDir = lang_path('en');
    if (! is_dir($enDir)) {
        mkdir($enDir, 0755, true);
    }

    file_put_contents($enDir.'/messages.php', "<?php\n\nreturn ['unused' => 'value'];");

    $tempPath = sys_get_temp_dir().'/translation-pruner-custom-'.uniqid();
    mkdir($tempPath, 0755, true);
    file_put_contents($tempPath.'/usage.php', "<?php echo 'noop';");

    config()->set('translation-pruner.paths', 'not-an-array');

    Helpers::runArtisan('translation:prune', ['--path' => $tempPath])
        ->expectsConfirmation('Delete these unused translations?', 'no')
        ->assertSuccessful();

    unlink($tempPath.'/usage.php');
    rmdir($tempPath);
});

it('runs with invalid configured paths when no option is provided', function () {
    config()->set('translation-pruner.paths', 'invalid');

    Helpers::runArtisan('translation:prune')
        ->expectsOutput('✅ No unused translations to remove!')
        ->assertSuccessful();
});
