<?php

declare(strict_types=1);

use VildanBina\TranslationPruner\Scanners\BladeScanner;

it('can handle blade files', function () {
    $scanner = new BladeScanner();

    expect($scanner->canHandle('welcome.blade.php'))->toBeTrue();
    expect($scanner->canHandle('auth/login.blade.php'))->toBeTrue();
});

it('cannot handle non-blade files', function () {
    $scanner = new BladeScanner();

    expect($scanner->canHandle('test.php'))->toBeFalse();
    expect($scanner->canHandle('test.vue'))->toBeFalse();
    expect($scanner->canHandle('test.js'))->toBeFalse();
});

it('scans @lang directive', function () {
    $scanner = new BladeScanner();
    $content = "@lang('messages.welcome')";

    $keys = $scanner->scan($content);

    expect($keys)->toContain('messages.welcome');
});

it('scans @choice directive', function () {
    $scanner = new BladeScanner();
    $content = "@choice('messages.items', 5)";

    $keys = $scanner->scan($content);

    expect($keys)->toContain('messages.items');
});

it('scans blade echo with __ function', function () {
    $scanner = new BladeScanner();
    $content = "{{ __('messages.welcome') }}";

    $keys = $scanner->scan($content);

    expect($keys)->toContain('messages.welcome');
});

it('scans blade echo with trans function', function () {
    $scanner = new BladeScanner();
    $content = "{{ trans('auth.login') }}";

    $keys = $scanner->scan($content);

    expect($keys)->toContain('auth.login');
});

it('scans trans_choice helper in blade', function () {
    $scanner = new BladeScanner();
    $content = "{{ trans_choice('messages.items', 2) }}";

    $keys = $scanner->scan($content);

    expect($keys)->toContain('messages.items');
});

it('scans multiple translations in blade template', function () {
    $scanner = new BladeScanner();
    $content = <<<'BLADE'
    <h1>{{ __('messages.welcome') }}</h1>
    <p>@lang('messages.description')</p>
    <button>{{ trans('auth.login') }}</button>
    BLADE;

    $keys = $scanner->scan($content);

    expect($keys)->toHaveCount(3)
        ->and($keys)->toContain('messages.welcome')
        ->and($keys)->toContain('messages.description')
        ->and($keys)->toContain('auth.login');
});

it('handles double quotes in blade', function () {
    $scanner = new BladeScanner();
    $content = '{{ __("messages.welcome") }}';

    $keys = $scanner->scan($content);

    expect($keys)->toContain('messages.welcome');
});

it('detects translations in livewire attributes', function () {
    $scanner = new BladeScanner();
    $content = '<div :title="__(\'messages.tooltip\')" wire:loading.attr="__(\'messages.loading\')"></div>';

    $keys = $scanner->scan($content);

    expect($keys)->toContain('messages.tooltip')
        ->and($keys)->toContain('messages.loading');
});
