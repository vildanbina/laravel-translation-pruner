<?php

use VildanBina\TranslationPruner\Scanners\PhpScanner;

it('can handle php files', function () {
    $scanner = new PhpScanner();

    expect($scanner->canHandle('php'))->toBeTrue();
});

it('cannot handle non-php files', function () {
    $scanner = new PhpScanner();

    expect($scanner->canHandle('blade'))->toBeFalse();
    expect($scanner->canHandle('vue'))->toBeFalse();
});

it('scans basic __ translations', function () {
    $scanner = new PhpScanner();
    $content = "<?php echo __('messages.welcome');";

    $keys = $scanner->scan($content);

    expect($keys)->toContain('messages.welcome');
});

it('scans trans function calls', function () {
    $scanner = new PhpScanner();
    $content = "<?php echo trans('auth.login');";

    $keys = $scanner->scan($content);

    expect($keys)->toContain('auth.login');
});

it('scans Lang::get calls', function () {
    $scanner = new PhpScanner();
    $content = "<?php echo Lang::get('validation.required');";

    $keys = $scanner->scan($content);

    expect($keys)->toContain('validation.required');
});

it('scans trans_choice calls', function () {
    $scanner = new PhpScanner();
    $content = "<?php echo trans_choice('messages.items', 5);";

    $keys = $scanner->scan($content);

    expect($keys)->toContain('messages.items');
});

it('scans multiple translations in one file', function () {
    $scanner = new PhpScanner();
    $content = <<<'PHP'
    <?php
    echo __('messages.welcome');
    echo trans('auth.login');
    echo Lang::get('validation.required');
    PHP;

    $keys = $scanner->scan($content);

    expect($keys)->toHaveCount(3)
        ->and($keys)->toContain('messages.welcome')
        ->and($keys)->toContain('auth.login')
        ->and($keys)->toContain('validation.required');
});

it('handles double quotes', function () {
    $scanner = new PhpScanner();
    $content = '<?php echo __("messages.welcome");';

    $keys = $scanner->scan($content);

    expect($keys)->toContain('messages.welcome');
});

it('removes duplicates', function () {
    $scanner = new PhpScanner();
    $content = <<<'PHP'
    <?php
    echo __('messages.welcome');
    echo __('messages.welcome');
    PHP;

    $keys = $scanner->scan($content);

    expect($keys)->toHaveCount(1);
});
