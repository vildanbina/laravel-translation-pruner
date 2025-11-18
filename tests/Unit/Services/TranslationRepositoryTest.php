<?php

declare(strict_types=1);

use Illuminate\Config\Repository as BaseConfigRepository;
use Illuminate\Filesystem\Filesystem;
use VildanBina\TranslationPruner\Loaders\JsonLoader;
use VildanBina\TranslationPruner\Loaders\PhpArrayLoader;
use VildanBina\TranslationPruner\Services\TranslationRepository;

final class TestTranslationRepository extends TranslationRepository
{
    /**
     * @var callable(): (?string)|null
     */
    private $baseOverride;

    /**
     * @var callable(): (string|false|null)|null
     */
    private $cwdOverride;

    public function __construct(
        BaseConfigRepository $config,
        Filesystem $filesystem,
        ?callable $baseOverride = null,
        ?callable $cwdOverride = null,
    ) {
        $this->baseOverride = $baseOverride;
        $this->cwdOverride = $cwdOverride;

        parent::__construct($config, $filesystem);
    }

    protected function resolveBaseLangPath(): ?string
    {
        if ($this->baseOverride !== null) {
            return ($this->baseOverride)();
        }

        return parent::resolveBaseLangPath();
    }

    protected function resolveWorkingDirectory(): string|false|null
    {
        if ($this->cwdOverride !== null) {
            return ($this->cwdOverride)();
        }

        return parent::resolveWorkingDirectory();
    }
}

beforeEach(function () {
    /** @var VildanBina\TranslationPruner\Tests\TestCase $this */
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
    /** @var VildanBina\TranslationPruner\Tests\TestCase $this */
    $this->filesystem->deleteDirectory($this->langPath);
});

it('loads json translations with metadata', function () {
    /** @var VildanBina\TranslationPruner\Tests\TestCase $this */
    file_put_contents($this->langPath.'/en.json', json_encode([
        'Welcome' => 'Welcome back',
    ]));

    $translations = $this->repository->all();

    expect($translations)->toHaveKey('Welcome.en.file')
        ->and($translations['Welcome']['en']['value'])->toBe('Welcome back')
        ->and($translations['Welcome']['en']['group'])->toBeNull();
});

it('loads php translations including nested keys', function () {
    /** @var VildanBina\TranslationPruner\Tests\TestCase $this */
    $this->filesystem->makeDirectory($this->langPath.'/en');
    file_put_contents($this->langPath.'/en/messages.php', <<<'PHP'
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
    /** @var VildanBina\TranslationPruner\Tests\TestCase $this */
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

it('skips json files when no loader can handle them', function () {
    /** @var VildanBina\TranslationPruner\Tests\TestCase $this */
    file_put_contents($this->langPath.'/en.json', json_encode(['Welcome' => 'hello']));

    $config = new BaseConfigRepository([
        'translation-pruner' => [
            'lang_path' => $this->langPath,
            'loaders' => [PhpArrayLoader::class],
        ],
    ]);

    $repository = new TranslationRepository($config, $this->filesystem);

    expect($repository->all())->toBe([]);
});

it('skips php files when no loader can handle them', function () {
    /** @var VildanBina\TranslationPruner\Tests\TestCase $this */
    $this->filesystem->makeDirectory($this->langPath.'/en');
    file_put_contents($this->langPath.'/en/messages.php', "<?php return ['key' => 'value'];");

    $config = new BaseConfigRepository([
        'translation-pruner' => [
            'lang_path' => $this->langPath,
            'loaders' => [JsonLoader::class],
        ],
    ]);

    $repository = new TranslationRepository($config, $this->filesystem);

    expect($repository->all())->toBe([]);
});

it('skips non-string filesystem entries', function () {
    $filesystem = new class extends Filesystem
    {
        public function isDirectory($directory)
        {
            return true;
        }

        /**
         * @return array<int, int>
         */
        public function glob($pattern, $flags = 0)
        {
            return [123];
        }

        /**
         * @param  array<int, string>|string|int  $depth
         * @return array<int, int>
         */
        public function directories($directory, $depth = 0)
        {
            return [123];
        }
    };

    $config = new BaseConfigRepository([
        'translation-pruner' => [
            'lang_path' => '/tmp/non-existent',
        ],
    ]);

    $repository = new TranslationRepository($config, $filesystem);

    expect($repository->all())->toBe([]);
});

it('skips invalid php files returned by glob', function () {
    $filesystem = new class extends Filesystem
    {
        public function isDirectory($directory)
        {
            return true;
        }

        /**
         * @param  array<int, string>|string|int  $depth
         * @return array<int, string>
         */
        public function directories($directory, $depth = 0)
        {
            return [$directory.'/en'];
        }

        /**
         * @return array<int, bool>
         */
        public function glob($pattern, $flags = 0)
        {
            return [false];
        }
    };

    $config = new BaseConfigRepository([
        'translation-pruner' => [
            'lang_path' => sys_get_temp_dir().'/translation-repository-invalid-'.uniqid(),
        ],
    ]);

    $repository = new TranslationRepository($config, $filesystem);

    expect($repository->all())->toBe([]);
});

it('falls back to default loaders when configuration is invalid', function () {
    /** @var VildanBina\TranslationPruner\Tests\TestCase $this */
    $invalidConfig = new BaseConfigRepository([
        'translation-pruner' => [
            'lang_path' => $this->langPath,
            'loaders' => [123, '', stdClass::class],
        ],
    ]);

    $repository = new TranslationRepository(
        config: $invalidConfig,
        filesystem: $this->filesystem,
    );

    expect($repository->getLoaderFor($this->langPath.'/file.json'))->toBeInstanceOf(JsonLoader::class)
        ->and($repository->getLoaderFor($this->langPath.'/file.php'))->toBeInstanceOf(PhpArrayLoader::class);

    expect($repository->getLoaderFor($this->langPath.'/file.txt'))->toBeNull();
});

it('returns default lang path when configuration is missing', function () {
    $config = new BaseConfigRepository(['translation-pruner' => []]);
    $repository = new TranslationRepository($config, new Filesystem());

    expect($repository->getLangPath())->toBe(base_path('lang'));
});

it('falls back to cwd when base path resolver returns null', function () {
    $config = new BaseConfigRepository(['translation-pruner' => []]);
    $repository = new TestTranslationRepository(
        config: $config,
        filesystem: new Filesystem(),
        baseOverride: static fn () => null,
        cwdOverride: static fn () => '/tmp/fallback'
    );

    expect($repository->getLangPath())->toBe('/tmp/fallback/lang');
});

it('falls back to lang when cwd resolver returns false', function () {
    $config = new BaseConfigRepository(['translation-pruner' => []]);
    $repository = new TestTranslationRepository(
        config: $config,
        filesystem: new Filesystem(),
        baseOverride: static fn () => null,
        cwdOverride: static fn () => false
    );

    expect($repository->getLangPath())->toBe('lang');
});

it('uses the current working directory when base path is missing', function () {
    $config = new BaseConfigRepository(['translation-pruner' => []]);
    $repository = new TestTranslationRepository(
        config: $config,
        filesystem: new Filesystem(),
        baseOverride: static fn () => null,
    );

    expect($repository->getLangPath())->toBe(getcwd().'/lang');
});
