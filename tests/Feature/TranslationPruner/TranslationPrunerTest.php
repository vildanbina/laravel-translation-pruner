<?php

declare(strict_types=1);

use VildanBina\TranslationPruner\TranslationPruner;

beforeEach(function () {
    /** @var VildanBina\TranslationPruner\Tests\TestCase $this */
    useTemporaryLangPath($this);
});

afterEach(function () {
    /** @var VildanBina\TranslationPruner\Tests\TestCase $this */
    restoreTemporaryLangPath($this);
});

it('can scan for translations', function () {
    $pruner = app(TranslationPruner::class);
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

    $pruner = app(TranslationPruner::class);
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

    $pruner = app(TranslationPruner::class);
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

    $pruner = app(TranslationPruner::class);
    $result = $pruner->scan([$testDir]);

    expect($result['total'])->toBe(2)
        ->and($result['used'])->toBe(1)
        ->and($result['unused'])->toBe(1);

    // Cleanup
    unlink($testDir.'/test.php');
    rmdir($testDir);
});

it('detects translations in react files', function () {
    $enDir = lang_path('en');
    if (! is_dir($enDir)) {
        mkdir($enDir, 0755, true);
    }

    file_put_contents($enDir.'/messages.php', "<?php\n\nreturn [\n    'react_used' => 'Used',\n    'react_translated' => 'Translated',\n    'react_unused' => 'Unused',\n];");

    $testDir = sys_get_temp_dir().'/translation-pruner-react-'.uniqid();
    mkdir($testDir, 0755, true);
    file_put_contents($testDir.'/Component.jsx', <<<'JSX'
    import { Trans, useTranslation } from 'react-i18next';

    export default function Example() {
        const { t } = useTranslation();

        return (
            <div title={t('messages.react_used')}>
                <Trans i18nKey="messages.react_translated" />
            </div>
        );
    }
    JSX);

    $pruner = app(TranslationPruner::class);
    $result = $pruner->scan([$testDir]);

    expect($result['unused_keys'])->toHaveKey('messages.react_unused')
        ->and($result['unused_keys'])->not->toHaveKey('messages.react_used')
        ->and($result['unused_keys'])->not->toHaveKey('messages.react_translated');

    unlink($testDir.'/Component.jsx');
    rmdir($testDir);
});

it('detects translations in livewire blade attributes', function () {
    $enDir = lang_path('en');
    if (! is_dir($enDir)) {
        mkdir($enDir, 0755, true);
    }

    file_put_contents($enDir.'/messages.php', "<?php\n\nreturn [\n    'tooltip' => 'Tooltip',\n    'unused' => 'Unused',\n];");

    $testDir = sys_get_temp_dir().'/translation-pruner-livewire-'.uniqid();
    mkdir($testDir, 0755, true);
    file_put_contents($testDir.'/component.blade.php', "<div :title=\"__('messages.tooltip')\"></div>");

    $pruner = app(TranslationPruner::class);
    $result = $pruner->scan([$testDir]);

    expect($result['unused_keys'])->toHaveKey('messages.unused')
        ->and($result['unused_keys'])->not->toHaveKey('messages.tooltip');

    unlink($testDir.'/component.blade.php');
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

    $pruner = app(TranslationPruner::class);
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

    $pruner = app(TranslationPruner::class);
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

    $pruner = app(TranslationPruner::class);
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

it('removes nested translations completely', function () {
    $enDir = lang_path('en');
    if (! is_dir($enDir)) {
        mkdir($enDir, 0755, true);
    }

    file_put_contents($enDir.'/messages.php', "<?php\n\nreturn [\n    'nested' => [\n        'child' => 'value',\n    ],\n];");

    $testDir = sys_get_temp_dir().'/translation-pruner-nested-'.uniqid();
    mkdir($testDir, 0755, true);
    file_put_contents($testDir.'/noop.php', "<?php echo 'noop';");

    $pruner = app(TranslationPruner::class);
    $result = $pruner->scan([$testDir]);

    expect($result['unused_keys'])->toHaveKey('messages.nested.child');

    $deleted = $pruner->prune($result['unused_keys'], dryRun: false);
    expect($deleted)->toBe(1);

    expect(file_exists($enDir.'/messages.php'))->toBeFalse();

    unlink($testDir.'/noop.php');
    rmdir($testDir);
});
