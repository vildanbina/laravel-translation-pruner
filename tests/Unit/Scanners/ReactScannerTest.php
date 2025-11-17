<?php

declare(strict_types=1);

use VildanBina\TranslationPruner\Scanners\ReactScanner;

it('can handle js and jsx files', function () {
    $scanner = new ReactScanner();

    expect($scanner->canHandle('Component.jsx'))->toBeTrue()
        ->and($scanner->canHandle('component.tsx'))->toBeTrue()
        ->and($scanner->canHandle('component.js'))->toBeTrue()
        ->and($scanner->canHandle('component.ts'))->toBeTrue();
});

it('cannot handle non-react files', function () {
    $scanner = new ReactScanner();

    expect($scanner->canHandle('test.php'))->toBeFalse()
        ->and($scanner->canHandle('template.blade.php'))->toBeFalse();
});

it('detects t helper usage', function () {
    $scanner = new ReactScanner();
    $content = "const { t } = useTranslation(); const title = t('messages.react');";

    expect($scanner->scan($content))->toContain('messages.react');
});

it('detects i18n.t usage', function () {
    $scanner = new ReactScanner();
    $content = "i18n.t('auth.login')";

    expect($scanner->scan($content))->toContain('auth.login');
});

it('detects Trans components', function () {
    $scanner = new ReactScanner();
    $content = '<Trans i18nKey="messages.component.title"></Trans>';

    expect($scanner->scan($content))->toContain('messages.component.title');
});

it('detects FormattedMessage ids', function () {
    $scanner = new ReactScanner();
    $content = '<FormattedMessage id="messages.intl.heading" defaultMessage="Heading" />';

    expect($scanner->scan($content))->toContain('messages.intl.heading');
});

it('detects template literal translations', function () {
    $scanner = new ReactScanner();
    $content = 'const label = t(`messages.template`);';

    expect($scanner->scan($content))->toContain('messages.template');
});
