<?php

declare(strict_types=1);

use VildanBina\TranslationPruner\Loaders\JsonLoader;

/** @var VildanBina\TranslationPruner\Tests\TestCase $this */
beforeEach(function () {
    /** @var VildanBina\TranslationPruner\Tests\TestCase $this */
    $this->tempJson = sys_get_temp_dir().'/translation-pruner-json-'.uniqid('.json', true);
});

afterEach(function () {
    /** @var VildanBina\TranslationPruner\Tests\TestCase $this */
    if ($this->tempJson !== '' && file_exists($this->tempJson)) {
        unlink($this->tempJson);
    }
});

it('loads translations from json file', function () {
    /** @var VildanBina\TranslationPruner\Tests\TestCase $this */
    $loader = new JsonLoader();
    $path = $this->tempJson;
    file_put_contents($path, json_encode(['welcome' => 'Welcome']));

    expect($loader->load($path))->toBe(['welcome' => 'Welcome']);
});

it('returns empty array for missing or invalid json file', function () {
    /** @var VildanBina\TranslationPruner\Tests\TestCase $this */
    $loader = new JsonLoader();

    expect($loader->load('/path/does/not/exist'))->toBe([]);

    $path = $this->tempJson;

    file_put_contents($path, 'not-json');
    expect($loader->load($path))->toBe([]);

    $directory = sys_get_temp_dir().'/translation-pruner-json-dir-'.uniqid();
    mkdir($directory);
    set_error_handler(static fn () => true);
    expect($loader->load($directory))->toBe([]);
    restore_error_handler();
    rmdir($directory);
});

it('saves and removes translation keys', function () {
    /** @var VildanBina\TranslationPruner\Tests\TestCase $this */
    $loader = new JsonLoader();
    $path = $this->tempJson;
    $loader->save($path, ['keep' => 'value', 'drop' => 'value']);

    expect($loader->remove($path, 'drop'))->toBeTrue()
        ->and($loader->load($path))->toBe(['keep' => 'value']);

    expect($loader->remove($path, 'keep'))->toBeTrue()
        ->and(file_exists($path))->toBeFalse();

    expect($loader->remove($path, 'missing'))->toBeFalse();
});

it('handles unreadable and unsavable files', function () {
    /** @var VildanBina\TranslationPruner\Tests\TestCase $this */
    $loader = new JsonLoader();

    $unreadable = sys_get_temp_dir().'/translation-pruner-json-unreadable-'.uniqid('.json', true);
    file_put_contents($unreadable, json_encode(['key' => 'value']));
    chmod($unreadable, 0);
    set_error_handler(static fn () => true);
    expect($loader->load($unreadable))->toBe([]);
    restore_error_handler();
    chmod($unreadable, 0644);
    unlink($unreadable);

    $resource = fopen('php://temp', 'r');
    if ($resource === false) {
        $this->fail('Unable to open temporary stream.');
    }

    $path = $this->tempJson;
    $result = $loader->save($path, ['resource' => $resource]);
    fclose($resource);

    expect($result)->toBeFalse();
});
