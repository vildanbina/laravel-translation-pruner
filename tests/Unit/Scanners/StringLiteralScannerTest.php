<?php

declare(strict_types=1);

use VildanBina\TranslationPruner\Scanners\StringLiteralScanner;

it('finds keys passed through metadata arrays and callbacks', function () {
    $content = <<<'JS'
    const navigation = [{ title: 'settings.security.label' }];
    const layout = { description: 'settings.security.confirmation.description' };
    const errors = ['settings.two_factor.errors.qr_code'];
    errors.map((key) => t(key));
    JS;

    expect((new StringLiteralScanner)->scan($content))->toEqualCanonicalizing([
        'settings.security.label',
        'settings.security.confirmation.description',
        'settings.two_factor.errors.qr_code',
    ]);
});

it('ignores commented references', function (string $content) {
    expect((new StringLiteralScanner)->scan($content))->toBe([]);
})->with([
    'javascript line' => "// t('messages.unused')",
    'block' => "/* const title = 'messages.unused'; */",
    'php line' => "<?php # __('messages.unused');",
    'php docblock' => "<?php /** __('messages.unused'); */",
    'html' => '<!-- <Trans i18nKey="messages.unused" /> -->',
    'blade' => "{{-- {{ __('messages.unused') }} --}}",
]);

it('keeps comment markers and escaped quotes inside strings intact', function () {
    $content = <<<'JS'
    const url = 'https://example.test';
    const description = "Don't // remove /* this */";
    const title = 'messages.profile.title';
    JS;

    expect((new StringLiteralScanner)->scan($content))->toContain(
        'https://example.test',
        "Don't // remove /* this */",
        'messages.profile.title',
    );
});

it('retains dynamic expressions and concatenation prefixes', function (string $content, string $reference) {
    expect((new StringLiteralScanner)->scan($content))->toContain($reference);
})->with([
    'template interpolation' => ['t(`messages.events.${event}.label`)', 'messages.events.${event}.label'],
    'php interpolation' => ['<?php __("messages.events.{$event}.label");', 'messages.events.{$event}.label'],
    'javascript concatenation' => ["t('messages.events.' + event)", 'messages.events.'],
    'php concatenation' => ["<?php __('messages.events.'.\$event);", 'messages.events.'],
]);

it('handles long literals without exceeding the regex stack limit', function () {
    $content = "const svg = '".str_repeat('path ', 20000)."'; const title = 'messages.title';";

    expect((new StringLiteralScanner)->scan($content))->toContain('messages.title');
});

it('keeps numeric literals as string references', function () {
    expect((new StringLiteralScanner)->scan("const values = ['0', '1'];"))->toBe(['0', '1']);
});

it('decodes escaped quotes and backslashes in literal keys', function () {
    $content = <<<'JS'
    const title = 'It\'s ready';
    const key = 'messages.back\\slash';
    JS;

    expect((new StringLiteralScanner)->scan($content))->toContain("It's ready", 'messages.back\slash');
});
