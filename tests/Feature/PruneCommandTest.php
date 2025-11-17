<?php

use function Pest\Laravel\artisan;

beforeEach(function () {
    $langPath = lang_path();
    if (!is_dir($langPath)) {
        mkdir($langPath, 0755, true);
    }
});

afterEach(function () {
    $langPath = lang_path();
    if (is_dir($langPath)) {
        array_map('unlink', glob("{$langPath}/*.json") ?: []);
        foreach (glob("{$langPath}/*", GLOB_ONLYDIR) ?: [] as $dir) {
            array_map('unlink', glob("{$dir}/*.php") ?: []);
            rmdir($dir);
        }
    }
});

it('can run prune command', function () {
    artisan('translation:prune')
        ->expectsOutput('Finding unused translations...')
        ->assertSuccessful();
});

it('shows no unused translations message', function () {
    artisan('translation:prune')
        ->expectsOutput('✅ No unused translations to remove!')
        ->assertSuccessful();
});

it('runs in dry run mode by default', function () {
    // Create translation file with unused key
    $enDir = lang_path('en');
    if (!is_dir($enDir)) { mkdir($enDir, 0755, true); }
    file_put_contents($enDir . '/messages.php', "<?php\n\nreturn [\n    'welcome' => 'Welcome',\n    'unused' => 'Not used',\n];");

    $testDir = sys_get_temp_dir() . '/translation-pruner-test-' . uniqid();
    mkdir($testDir, 0755, true);
    file_put_contents($testDir . '/test.php', "<?php echo __('messages.welcome');");

    config()->set('translation-pruner.paths', [$testDir]);

    artisan('translation:prune')
        ->expectsOutputToContain('🔍 DRY RUN MODE')
        ->assertSuccessful();

    // File should still have unused key
    $translations = include $enDir . '/messages.php';
    expect($translations)->toHaveKey('unused');

    // Cleanup
    unlink($testDir . '/test.php');
    rmdir($testDir);
});

it('shows dry run instructions', function () {
    // Create translation file with unused key
    $enDir = lang_path('en');
    if (!is_dir($enDir)) { mkdir($enDir, 0755, true); }
    file_put_contents($enDir . '/messages.php', "<?php\n\nreturn ['unused' => 'Not used'];");

    artisan('translation:prune')
        ->expectsOutputToContain('To actually delete these translations, run with --dry-run=false')
        ->assertSuccessful();
});

it('lists unused translations before pruning', function () {
    // Create translation file with unused key
    $enDir = lang_path('en');
    if (!is_dir($enDir)) { mkdir($enDir, 0755, true); }
    file_put_contents($enDir . '/messages.php', "<?php\n\nreturn ['unused' => 'Not used'];");

    artisan('translation:prune')
        ->expectsOutputToContain('Found 1 unused translations:')
        ->expectsOutputToContain('• messages.unused')
        ->assertSuccessful();
});

it('can delete translations with force flag', function () {
    // Create translation file with unused key
    $enDir = lang_path('en');
    if (!is_dir($enDir)) { mkdir($enDir, 0755, true); }
    file_put_contents($enDir . '/messages.php', "<?php\n\nreturn [\n    'welcome' => 'Welcome',\n    'unused' => 'Not used',\n];");

    $testDir = sys_get_temp_dir() . '/translation-pruner-test-' . uniqid();
    mkdir($testDir, 0755, true);
    file_put_contents($testDir . '/test.php', "<?php echo __('messages.welcome');");

    config()->set('translation-pruner.paths', [$testDir]);

    artisan('translation:prune', ['--dry-run' => false, '--force' => true])
        ->expectsOutputToContain('✅ Deleted 1 unused translations')
        ->assertSuccessful();

    // Verify unused key was removed
    $translations = include $enDir . '/messages.php';
    expect($translations)->toHaveKey('welcome')
        ->and($translations)->not->toHaveKey('unused');

    // Cleanup
    unlink($testDir . '/test.php');
    rmdir($testDir);
});