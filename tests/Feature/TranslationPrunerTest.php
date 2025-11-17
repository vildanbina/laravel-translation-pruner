<?php

declare(strict_types=1);

use VildanBina\TranslationPruner\TranslationPruner;

beforeEach(function () {
    // Create test lang directory
    $langPath = lang_path();
    if (! is_dir($langPath)) {
        mkdir($langPath, 0755, true);
    }
});

afterEach(function () {
    // Clean up test lang directory
    $langPath = lang_path();
    if (is_dir($langPath)) {
        array_map('unlink', glob("{$langPath}/*.json") ?: []);
        foreach (glob("{$langPath}/*", GLOB_ONLYDIR) ?: [] as $dir) {
            array_map('unlink', glob("{$dir}/*.php") ?: []);
            rmdir($dir);
        }
    }
});

it('can scan for translations', function () {
    $pruner = new TranslationPruner();
    $result = $pruner->scan();

    expect($result)->toHaveKey('total')
        ->and($result)->toHaveKey('used')
        ->and($result)->toHaveKey('unused')
        ->and($result)->toHaveKey('unused_keys');
});

it('finds unused translations', function () {
    // Create translation files
    $enDir = lang_path('en');
    if (! is_dir($enDir)) {
        if (! is_dir($enDir)) {
            mkdir($enDir, 0755, true);
        }
    }

    file_put_contents($enDir.'/messages.php', "<?php\n\nreturn [\n    'welcome' => 'Welcome',\n    'unused' => 'Not used',\n];");

    // Create code that uses only one translation
    $testDir = sys_get_temp_dir().'/translation-pruner-test-'.uniqid();
    mkdir($testDir, 0755, true);
    file_put_contents($testDir.'/test.php', "<?php echo __('messages.welcome');");

    $pruner = new TranslationPruner();
    $result = $pruner->scan([$testDir]);

    expect($result['unused'])->toBe(1)
        ->and($result['unused_keys'])->toHaveKey('messages.unused');

    // Cleanup
    unlink($testDir.'/test.php');
    rmdir($testDir);
});

it('identifies used translations', function () {
    // Create translation file
    $enDir = lang_path('en');
    if (! is_dir($enDir)) {
        mkdir($enDir, 0755, true);
    }
    file_put_contents($enDir.'/messages.php', "<?php\n\nreturn [\n    'welcome' => 'Welcome',\n];");

    // Create code that uses the translation
    $testDir = sys_get_temp_dir().'/translation-pruner-test-'.uniqid();
    mkdir($testDir, 0755, true);
    file_put_contents($testDir.'/test.php', "<?php echo __('messages.welcome');");

    $pruner = new TranslationPruner();
    $result = $pruner->scan([$testDir]);

    expect($result['used'])->toBe(1)
        ->and($result['unused'])->toBe(0);

    // Cleanup
    unlink($testDir.'/test.php');
    rmdir($testDir);
});

it('handles JSON translations', function () {
    // Create JSON translation file
    file_put_contents(lang_path('en.json'), json_encode(['Welcome' => 'Welcome', 'Goodbye' => 'Goodbye']));

    // Create code that uses only one
    $testDir = sys_get_temp_dir().'/translation-pruner-test-'.uniqid();
    mkdir($testDir, 0755, true);
    file_put_contents($testDir.'/test.php', "<?php echo __('Welcome');");

    $pruner = new TranslationPruner();
    $result = $pruner->scan([$testDir]);

    expect($result['total'])->toBe(2)
        ->and($result['used'])->toBe(1)
        ->and($result['unused'])->toBe(1);

    // Cleanup
    unlink($testDir.'/test.php');
    rmdir($testDir);
});

it('respects exclusion patterns', function () {
    // Create translation file
    $enDir = lang_path('en');
    if (! is_dir($enDir)) {
        mkdir($enDir, 0755, true);
    }
    file_put_contents($enDir.'/validation.php', "<?php\n\nreturn [\n    'required' => 'Required',\n    'email' => 'Email',\n];");

    // Don't use any of them in code
    $testDir = sys_get_temp_dir().'/translation-pruner-test-'.uniqid();
    mkdir($testDir, 0755, true);
    file_put_contents($testDir.'/test.php', "<?php echo 'test';");

    $pruner = new TranslationPruner(['validation.*']);
    $result = $pruner->scan([$testDir]);

    // Should not find them as unused because they're excluded
    expect($result['unused'])->toBe(0);

    // Cleanup
    unlink($testDir.'/test.php');
    rmdir($testDir);
});

it('can prune translations in dry run mode', function () {
    // Create translation file
    $enDir = lang_path('en');
    if (! is_dir($enDir)) {
        mkdir($enDir, 0755, true);
    }
    file_put_contents($enDir.'/messages.php', "<?php\n\nreturn [\n    'welcome' => 'Welcome',\n    'unused' => 'Not used',\n];");

    $testDir = sys_get_temp_dir().'/translation-pruner-test-'.uniqid();
    mkdir($testDir, 0755, true);
    file_put_contents($testDir.'/test.php', "<?php echo __('messages.welcome');");

    $pruner = new TranslationPruner();
    $result = $pruner->scan([$testDir]);

    $deleted = $pruner->prune($result['unused_keys'], dryRun: true);

    expect($deleted)->toBe(1);

    // File should still exist (dry run)
    expect(file_exists($enDir.'/messages.php'))->toBeTrue();

    // Cleanup
    unlink($testDir.'/test.php');
    rmdir($testDir);
});

it('can actually delete unused translations', function () {
    // Create translation file
    $enDir = lang_path('en');
    if (! is_dir($enDir)) {
        mkdir($enDir, 0755, true);
    }
    file_put_contents($enDir.'/messages.php', "<?php\n\nreturn [\n    'welcome' => 'Welcome',\n    'unused' => 'Not used',\n];");

    $testDir = sys_get_temp_dir().'/translation-pruner-test-'.uniqid();
    mkdir($testDir, 0755, true);
    file_put_contents($testDir.'/test.php', "<?php echo __('messages.welcome');");

    $pruner = new TranslationPruner();
    $result = $pruner->scan([$testDir]);

    $deleted = $pruner->prune($result['unused_keys'], dryRun: false);

    expect($deleted)->toBe(1);

    // Verify the unused key was actually removed
    $translations = include $enDir.'/messages.php';
    expect($translations)->toHaveKey('welcome')
        ->and($translations)->not->toHaveKey('unused');

    // Cleanup
    unlink($testDir.'/test.php');
    rmdir($testDir);
});
