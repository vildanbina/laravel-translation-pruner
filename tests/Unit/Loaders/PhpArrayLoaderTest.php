<?php

declare(strict_types=1);

use VildanBina\TranslationPruner\Loaders\PhpArrayLoader;

beforeEach(function () {
    /** @var VildanBina\TranslationPruner\Tests\TestCase $this */
    $this->tempDir = sys_get_temp_dir().'/translation-pruner-test-'.uniqid();
    mkdir($this->tempDir, 0777, true);
    $this->testFile = $this->tempDir.'/test.php';
});

afterEach(function () {
    /** @var VildanBina\TranslationPruner\Tests\TestCase $this */
    if (file_exists($this->testFile)) {
        unlink($this->testFile);
    }
    if (is_dir($this->tempDir)) {
        rmdir($this->tempDir);
    }
});

it('can handle php files', function () {
    /** @var VildanBina\TranslationPruner\Tests\TestCase $this */
    $loader = new PhpArrayLoader();

    expect($loader->canHandle('test.php'))->toBeTrue();
    expect($loader->canHandle('/path/to/file.php'))->toBeTrue();
});

it('cannot handle non-php files', function () {
    /** @var VildanBina\TranslationPruner\Tests\TestCase $this */
    $loader = new PhpArrayLoader();

    expect($loader->canHandle('test.json'))->toBeFalse();
    expect($loader->canHandle('test.txt'))->toBeFalse();
});

it('loads translations from php file', function () {
    /** @var VildanBina\TranslationPruner\Tests\TestCase $this */
    file_put_contents($this->testFile, "<?php\n\nreturn ['key' => 'value'];");
    $loader = new PhpArrayLoader();

    $translations = $loader->load($this->testFile);

    expect($translations)->toBe(['key' => 'value']);
});

it('returns empty array for non-existent file', function () {
    /** @var VildanBina\TranslationPruner\Tests\TestCase $this */
    $loader = new PhpArrayLoader();

    $translations = $loader->load('/non/existent/file.php');

    expect($translations)->toBe([]);
});

it('saves translations with single quotes correctly', function () {
    /** @var VildanBina\TranslationPruner\Tests\TestCase $this */
    $loader = new PhpArrayLoader();
    $translations = [
        'welcome' => "Hello 'world'",
        'quote' => "It's a beautiful day",
        'multiple' => "I'm saying 'hello' to you",
    ];

    $loader->save($this->testFile, $translations);

    // Reload to verify it was saved correctly
    $loaded = $loader->load($this->testFile);
    expect($loaded)->toBe($translations);
});

it('saves translations with backslashes correctly', function () {
    /** @var VildanBina\TranslationPruner\Tests\TestCase $this */
    $loader = new PhpArrayLoader();
    $translations = [
        'path' => 'C:\Users\John\Documents',
        'regex' => 'Use \d+ for digits',
        'escape' => 'This is a backslash: \\',
    ];

    $loader->save($this->testFile, $translations);

    // Reload to verify it was saved correctly
    $loaded = $loader->load($this->testFile);
    expect($loaded)->toBe($translations);
});

it('saves translations with both quotes and backslashes correctly', function () {
    /** @var VildanBina\TranslationPruner\Tests\TestCase $this */
    $loader = new PhpArrayLoader();
    $translations = [
        'complex' => "It's in C:\Users\John's folder",
        'mixed' => "Path: \\server\share and it's 'working'",
        'edge_case' => "End with backslash and quote: \\'",
    ];

    $loader->save($this->testFile, $translations);

    // Reload to verify it was saved correctly
    $loaded = $loader->load($this->testFile);
    expect($loaded)->toBe($translations);
});

it('saves translations with special characters correctly', function () {
    /** @var VildanBina\TranslationPruner\Tests\TestCase $this */
    $loader = new PhpArrayLoader();
    $translations = [
        'newline' => "Line 1\nLine 2",
        'tab' => "Column1\tColumn2",
        'unicode' => 'Hello 世界 🌍',
        'html' => '<p>This is <strong>bold</strong></p>',
    ];

    $loader->save($this->testFile, $translations);

    // Reload to verify it was saved correctly
    $loaded = $loader->load($this->testFile);
    expect($loaded)->toBe($translations);
});

it('saves translations with different types correctly', function () {
    /** @var VildanBina\TranslationPruner\Tests\TestCase $this */
    $loader = new PhpArrayLoader();
    $translations = [
        'string' => 'text',
        'integer' => 42,
        'float' => 3.14,
        'boolean_true' => true,
        'boolean_false' => false,
        'null_value' => null,
    ];

    $loader->save($this->testFile, $translations);

    // Reload to verify it was saved correctly
    $loaded = $loader->load($this->testFile);
    expect($loaded)->toBe($translations);
});

it('saves nested arrays correctly', function () {
    /** @var VildanBina\TranslationPruner\Tests\TestCase $this */
    $loader = new PhpArrayLoader();
    $translations = [
        'messages' => [
            'welcome' => 'Welcome',
            'goodbye' => 'Goodbye',
        ],
        'validation' => [
            'required' => 'Field is required',
        ],
    ];

    $loader->save($this->testFile, $translations);

    // Reload to verify it was saved correctly
    $loaded = $loader->load($this->testFile);
    expect($loaded)->toBe($translations);
});

it('saves empty array correctly', function () {
    /** @var VildanBina\TranslationPruner\Tests\TestCase $this */
    $loader = new PhpArrayLoader();
    $translations = [];

    $loader->save($this->testFile, $translations);

    // Reload to verify it was saved correctly
    $loaded = $loader->load($this->testFile);
    expect($loaded)->toBe([]);
});

it('removes translation key correctly', function () {
    /** @var VildanBina\TranslationPruner\Tests\TestCase $this */
    $loader = new PhpArrayLoader();
    $translations = [
        'key1' => 'value1',
        'key2' => 'value2',
    ];

    $loader->save($this->testFile, $translations);
    $removed = $loader->remove($this->testFile, 'test.key1', 'test');

    expect($removed)->toBeTrue();

    $loaded = $loader->load($this->testFile);
    expect($loaded)->toBe(['key2' => 'value2']);
});

it('deletes file when removing last translation', function () {
    /** @var VildanBina\TranslationPruner\Tests\TestCase $this */
    $loader = new PhpArrayLoader();
    $translations = ['key1' => 'value1'];

    $loader->save($this->testFile, $translations);
    $loader->remove($this->testFile, 'test.key1', 'test');

    expect(file_exists($this->testFile))->toBeFalse();
});

it('returns false when removing non-existent key', function () {
    /** @var VildanBina\TranslationPruner\Tests\TestCase $this */
    $loader = new PhpArrayLoader();
    $translations = ['key1' => 'value1'];

    $loader->save($this->testFile, $translations);
    $removed = $loader->remove($this->testFile, 'test.nonexistent', 'test');

    expect($removed)->toBeFalse();
});

it('removes nested translation keys', function () {
    /** @var VildanBina\TranslationPruner\Tests\TestCase $this */
    $loader = new PhpArrayLoader();
    $translations = [
        'nested' => [
            'child' => 'value1',
            'other' => 'value2',
        ],
    ];

    $loader->save($this->testFile, $translations);

    // Supports removal when a fully-qualified key is provided
    $removed = $loader->remove($this->testFile, 'messages.nested.child', 'messages');
    expect($removed)->toBeTrue();

    $loaded = $loader->load($this->testFile);
    expect($loaded['nested'])->toHaveKey('other')
        ->and($loaded['nested'])->not->toHaveKey('child');

    // Also supports relative keys when the pruner passes only the nested path
    $loader->remove($this->testFile, 'nested.other', 'messages');

    expect(file_exists($this->testFile))->toBeFalse();
});
