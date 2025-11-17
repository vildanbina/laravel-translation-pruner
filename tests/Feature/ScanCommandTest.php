<?php

use function Pest\Laravel\artisan;

beforeEach(function () {
    $langPath = lang_path();
    if (!is_dir($langPath)) {
        mkdir($langPath, 0755, true);
    }
});

afterEach(function () {
    $langPath = lang_path();
    if (is_dir($langPath)) {
        array_map('unlink', glob("{$langPath}/*.json") ?: []);
        foreach (glob("{$langPath}/*", GLOB_ONLYDIR) ?: [] as $dir) {
            array_map('unlink', glob("{$dir}/*.php") ?: []);
            rmdir($dir);
        }
    }
});

it('can run scan command', function () {
    artisan('translation:scan')
        ->expectsOutput('Scanning for translations...')
        ->assertSuccessful();
});

it('shows no unused translations message when all are used', function () {
    artisan('translation:scan')
        ->expectsOutput('✅ No unused translations found!')
        ->assertSuccessful();
});

it('lists unused translations', function () {
    // Create translation file with unused key
    $enDir = lang_path('en');
    if (!is_dir($enDir)) { mkdir($enDir, 0755, true); }
    file_put_contents($enDir . '/messages.php', "<?php\n\nreturn [\n    'welcome' => 'Welcome',\n    'unused' => 'Not used',\n];");

    // Create code that uses only welcome
    $testDir = sys_get_temp_dir() . '/translation-pruner-test-' . uniqid();
    mkdir($testDir, 0755, true);
    file_put_contents($testDir . '/test.php', "<?php echo __('messages.welcome');");

    config()->set('translation-pruner.paths', [$testDir]);

    artisan('translation:scan')
        ->expectsOutputToContain('Unused translations:')
        ->expectsOutputToContain('messages.unused')
        ->assertSuccessful();

    // Cleanup
    unlink($testDir . '/test.php');
    rmdir($testDir);
});

it('can save results to file', function () {
    $outputFile = base_path('test-results.json');

    artisan('translation:scan', ['--save' => $outputFile])
        ->assertSuccessful();

    expect(file_exists($outputFile))->toBeTrue();

    $results = json_decode(file_get_contents($outputFile), true);
    expect($results)->toHaveKey('total')
        ->and($results)->toHaveKey('used')
        ->and($results)->toHaveKey('unused')
        ->and($results)->toHaveKey('unused_keys');

    // Cleanup
    unlink($outputFile);
});