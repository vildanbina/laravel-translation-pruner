<?php

declare(strict_types=1);

use Illuminate\Config\Repository;
use Illuminate\Filesystem\Filesystem;
use VildanBina\TranslationPruner\Services\TranslationRepository;
use VildanBina\TranslationPruner\Services\UsageScanner;
use VildanBina\TranslationPruner\TranslationPruner;

beforeEach(function () {
    $this->filesystem = new Filesystem;
    $this->tempDir = sys_get_temp_dir().'/translation-pruner-references-'.uniqid();
    $this->filesystem->makeDirectory($this->tempDir.'/lang/en', 0755, true);
    $this->filesystem->makeDirectory($this->tempDir.'/source/pages/settings/security/components', 0755, true);
    $this->config = new Repository([
        'translation-pruner' => [
            'lang_path' => $this->tempDir.'/lang',
            'paths' => [$this->tempDir.'/source'],
            'exclude' => [],
        ],
    ]);
});

afterEach(function () {
    $this->filesystem->deleteDirectory($this->tempDir);
});

it('prunes only unreferenced keys while preserving indirect usage in deeply nested source files', function () {
    $file = $this->tempDir.'/lang/en/settings.php';
    file_put_contents($file, <<<'PHP'
    <?php
    return [
        'security' => ['label' => 'Security', 'unused' => 'Unused'],
        'two_factor' => ['errors' => ['qr_code' => 'QR error', 'unused' => 'Unused error']],
    ];
    PHP);
    file_put_contents($this->tempDir.'/source/pages/settings/security/components/navigation.tsx', <<<'JS'
    const groups = ['settings'];
    const permissions = ['*'];
    const queryColumn = 'settings.*';
    const queryOrder = 'settings.' + column;
    const items = [{ title: 'settings.security.label' }];
    const errors = ['settings.two_factor.errors.qr_code'];
    items.map((item) => t(item.title));
    errors.map((key) => t(key));
    // t('settings.security.unused');
    JS);
    $pruner = new TranslationPruner($this->config,
        new TranslationRepository($this->config, $this->filesystem), new UsageScanner($this->config));

    $result = $pruner->scan();

    expect(array_keys($result['unused_keys']))->toBe([
        'settings.security.unused',
        'settings.two_factor.errors.unused',
    ]);

    expect($pruner->prune($result['unused_keys'], dryRun: false))->toBe(2);
    expect(include $file)->toBe([
        'security' => ['label' => 'Security'],
        'two_factor' => ['errors' => ['qr_code' => 'QR error']],
    ]);
});

it('preserves JSON keys stored in variables without keeping unrelated entries', function () {
    file_put_contents($this->tempDir.'/lang/en.json', json_encode([
        'Welcome back' => 'Welcome back',
        'Goodbye' => 'Goodbye',
    ], JSON_THROW_ON_ERROR));
    file_put_contents($this->tempDir.'/source/component.tsx', "const title = 'Welcome back'; t(title);");
    $pruner = new TranslationPruner($this->config,
        new TranslationRepository($this->config, $this->filesystem), new UsageScanner($this->config));

    $result = $pruner->scan();

    expect(array_keys($result['unused_keys']))->toBe(['Goodbye']);
});

it('handles numeric JSON keys when scanning and pruning', function () {
    $file = $this->tempDir.'/lang/en.json';
    file_put_contents($file, json_encode(['0' => 'Zero', '1' => 'One'], JSON_THROW_ON_ERROR));
    file_put_contents($this->tempDir.'/source/component.tsx', "const key = '1'; t(key);");
    $pruner = new TranslationPruner($this->config,
        new TranslationRepository($this->config, $this->filesystem), new UsageScanner($this->config));

    $result = $pruner->scan();

    expect(array_keys($result['unused_keys']))->toBe([0]);
    expect($pruner->prune($result['unused_keys'], dryRun: false))->toBe(1);
    expect(json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR))->toBe([1 => 'One']);
});

it('preserves keys under a translated concatenation prefix', function (string $fileName, string $source) {
    file_put_contents($this->tempDir.'/lang/en/messages.php', <<<'PHP'
    <?php
    return ['events' => ['created' => 'Created', 'updated' => 'Updated'], 'unused' => 'Unused'];
    PHP);
    file_put_contents($this->tempDir.'/source/'.$fileName, $source);
    $pruner = new TranslationPruner($this->config,
        new TranslationRepository($this->config, $this->filesystem), new UsageScanner($this->config));

    $result = $pruner->scan();

    expect(array_keys($result['unused_keys']))->toBe(['messages.unused']);
})->with([
    'javascript' => ['component.tsx', "t('messages.events.' + event);"],
    'php' => ['component.php', "<?php __('messages.events.'.\$event);"],
]);

it('preserves matching dynamic keys while still finding unused siblings', function (string $fileName, string $source) {
    file_put_contents($this->tempDir.'/lang/en/messages.php', <<<'PHP'
    <?php
    return [
        'events' => [
            'created' => ['label' => 'Created', 'description' => 'Unused'],
            'updated' => ['label' => 'Updated'],
        ],
        'unused' => 'Unused',
    ];
    PHP);
    file_put_contents($this->tempDir.'/source/'.$fileName, $source);
    $pruner = new TranslationPruner($this->config,
        new TranslationRepository($this->config, $this->filesystem), new UsageScanner($this->config));

    $result = $pruner->scan();

    expect(array_keys($result['unused_keys']))->toBe([
        'messages.events.created.description',
        'messages.unused',
    ]);
})->with([
    'javascript template' => ['component.tsx', 't(`messages.events.${event}.label`);'],
    'php interpolation' => ['component.php', '<?php __("messages.events.{$event}.label");'],
]);

it('keeps descendants of translated arrays with a group boundary', function (string $reference, array $unused) {
    file_put_contents($this->tempDir.'/lang/en/messages.php', <<<'PHP'
    <?php
    return ['nested' => ['title' => 'Title'], 'unused' => 'Unused'];
    PHP);
    file_put_contents($this->tempDir.'/lang/en/messages_extra.php', "<?php return ['unused' => 'Unused'];");
    file_put_contents($this->tempDir.'/source/component.php', "<?php trans('{$reference}');");
    $pruner = new TranslationPruner($this->config,
        new TranslationRepository($this->config, $this->filesystem), new UsageScanner($this->config));

    $result = $pruner->scan();

    expect(array_keys($result['unused_keys']))->toBe($unused);
})->with([
    'whole group' => ['messages', ['messages_extra.unused']],
    'nested array' => ['messages.nested', ['messages.unused', 'messages_extra.unused']],
]);
