<?php

declare(strict_types=1);

beforeEach(function () {
    /** @var VildanBina\TranslationPruner\Tests\TestCase $this */
    useTemporaryLangPath($this);
});

afterEach(function () {
    /** @var VildanBina\TranslationPruner\Tests\TestCase $this */
    restoreTemporaryLangPath($this);
});

it('can run prune command', function () {
    runArtisan('translation:prune')
        ->expectsOutput('Finding unused translations...')
        ->assertSuccessful();
});

it('shows no unused translations message', function () {
    runArtisan('translation:prune')
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

    runArtisan('translation:prune', ['--dry-run' => true])
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

    runArtisan('translation:prune')
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

    runArtisan('translation:prune')
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

    runArtisan('translation:prune', ['--force' => true])
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

    runArtisan('translation:prune', ['--path' => [$isolatedDir]])
        ->expectsOutputToContain('messages.special')
        ->expectsConfirmation('Delete these unused translations?', 'no')
        ->assertSuccessful();

    unlink($configDir.'/uses.php');
    rmdir($configDir);
    unlink($isolatedDir.'/noop.php');
    rmdir($isolatedDir);

    config()->set('translation-pruner.paths', [
        fixturesPath('app'),
        fixturesPath('resources'),
    ]);
});
