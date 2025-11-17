<?php

declare(strict_types=1);

use VildanBina\TranslationPruner\Scanners\VueScanner;

it('can handle vue and js files', function () {
    $scanner = new VueScanner();

    expect($scanner->canHandle('App.vue'))->toBeTrue();
    expect($scanner->canHandle('app.js'))->toBeTrue();
    expect($scanner->canHandle('types.ts'))->toBeTrue();
    expect($scanner->canHandle('Component.jsx'))->toBeTrue();
    expect($scanner->canHandle('Component.tsx'))->toBeTrue();
});

it('cannot handle other files', function () {
    $scanner = new VueScanner();

    expect($scanner->canHandle('test.php'))->toBeFalse();
    expect($scanner->canHandle('test.blade.php'))->toBeFalse();
});

it('scans $t function calls', function () {
    $scanner = new VueScanner();
    $content = "this.\$t('messages.welcome')";

    $keys = $scanner->scan($content);

    expect($keys)->toContain('messages.welcome');
});

it('scans i18n.t function calls', function () {
    $scanner = new VueScanner();
    $content = "i18n.t('auth.login')";

    $keys = $scanner->scan($content);

    expect($keys)->toContain('auth.login');
});

it('scans v-t directive', function () {
    $scanner = new VueScanner();
    $content = '<div v-t="messages.welcome"></div>';

    $keys = $scanner->scan($content);

    expect($keys)->toContain('messages.welcome');
});

it('scans .t method calls', function () {
    $scanner = new VueScanner();
    $content = "const message = this.t('messages.hello');";

    $keys = $scanner->scan($content);

    expect($keys)->toContain('messages.hello');
});

it('scans multiple translations in vue file', function () {
    $scanner = new VueScanner();
    $content = <<<'VUE'
    <template>
        <div>
            <h1>{{ $t('messages.welcome') }}</h1>
            <p v-t="messages.description"></p>
        </div>
    </template>
    <script>
    export default {
        mounted() {
            console.log(i18n.t('auth.login'));
        }
    }
    </script>
    VUE;

    $keys = $scanner->scan($content);

    expect($keys)->toContain('messages.welcome')
        ->and($keys)->toContain('messages.description')
        ->and($keys)->toContain('auth.login');
});

it('handles double quotes in vue', function () {
    $scanner = new VueScanner();
    $content = 'this.$t("messages.welcome")';

    $keys = $scanner->scan($content);

    expect($keys)->toContain('messages.welcome');
});
