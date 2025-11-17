<?php

declare(strict_types=1);

use Illuminate\Config\Repository as BaseConfigRepository;
use Illuminate\Filesystem\Filesystem;
use VildanBina\TranslationPruner\Loaders\JsonLoader;
use VildanBina\TranslationPruner\Loaders\PhpArrayLoader;
use VildanBina\TranslationPruner\Services\TranslationRepository;

beforeEach(function () {
    $this->filesystem = new Filesystem();
    $this->langPath = sys_get_temp_dir().'/translation-repository-test-'.uniqid();
    $this->filesystem->makeDirectory($this->langPath, 0777, true, true);

    $this->config = new BaseConfigRepository([
        'translation-pruner' => [
            'lang_path' => $this->langPath,
            'loaders' => [JsonLoader::class, PhpArrayLoader::class],
        ],
    ]);

    $this->repository = new TranslationRepository(
        config: $this->config,
        filesystem: $this->filesystem,
    );
});

afterEach(function () {
    if (isset($this->filesystem) && isset($this->langPath)) {
        $this->filesystem->deleteDirectory($this->langPath);
    }
});

it('loads json translations with metadata', function () {
    file_put_contents($this->langPath.'/en.json', json_encode([
        'Welcome' => 'Welcome back',
    ]));

    $translations = $this->repository->all();

    expect($translations)->toHaveKey('Welcome.en.file')
        ->and($translations['Welcome']['en']['value'])->toBe('Welcome back')
        ->and($translations['Welcome']['en']['group'])->toBeNull();
});

it('loads php translations including nested keys', function () {
    $this->filesystem->makeDirectory($this->langPath.'/en');
    file_put_contents($this->langPath.'/en/messages.php', <<<PHP
    <?php

    return [
        'simple' => 'value',
        'nested' => [
            'child' => 'child value',
        ],
    ];
    PHP);

    $translations = $this->repository->all();

    expect($translations)->toHaveKey('messages.simple')
        ->and($translations['messages.simple'])->toHaveKey('en')
        ->and($translations)->toHaveKey('messages.nested.child')
        ->and($translations['messages.nested.child']['en']['key_path'])->toBe('nested.child');
});

it('returns empty collection when lang path missing', function () {
    $missingConfig = new BaseConfigRepository([
        'translation-pruner' => [
            'lang_path' => $this->langPath.'/missing',
            'loaders' => [JsonLoader::class, PhpArrayLoader::class],
        ],
    ]);

    $repository = new TranslationRepository(
        config: $missingConfig,
        filesystem: $this->filesystem,
    );

    expect($repository->all())->toBe([]);
});
