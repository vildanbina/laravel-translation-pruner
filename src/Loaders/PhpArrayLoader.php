<?php

declare(strict_types=1);

namespace VildanBina\TranslationPruner\Loaders;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use VildanBina\TranslationPruner\Contracts\LoaderInterface;

class PhpArrayLoader implements LoaderInterface
{
    public function canHandle(string $file): bool
    {
        return pathinfo($file, PATHINFO_EXTENSION) === 'php';
    }

    public function load(string $file): array
    {
        if (! file_exists($file)) {
            return [];
        }

        $translations = include $file;

        return is_array($translations) ? $translations : [];
    }

    public function remove(string $file, string $fullKey, ?string $group = null): bool
    {
        $translations = $this->load($file);
        $targetKey = $fullKey;

        if ($group !== null) {
            $targetKey = Str::startsWith($targetKey, $group.'.')
                ? Str::after($targetKey, $group.'.')
                : $targetKey;
        }

        if (! Arr::has($translations, $targetKey)) {
            return false;
        }

        Arr::forget($translations, $targetKey);
        $translations = $this->pruneEmptyBranches($translations);

        if (empty($translations)) {
            return unlink($file);
        }

        return $this->save($file, $translations);
    }

    public function save(string $file, array $translations): bool
    {
        $content = "<?php\n\nreturn ".$this->formatArray($translations).";\n";

        return file_put_contents($file, $content) !== false;
    }

    private function formatArray(array $array, int $depth = 0): string
    {
        if (empty($array)) {
            return '[]';
        }

        $indent = str_repeat('    ', $depth);
        $items = [];

        foreach ($array as $key => $value) {
            $formattedKey = $this->formatValue($key);
            $formattedValue = is_array($value)
                ? $this->formatArray($value, $depth + 1)
                : $this->formatValue($value);

            $items[] = "{$indent}    {$formattedKey} => {$formattedValue}";
        }

        return "[\n".implode(",\n", $items).",\n{$indent}]";
    }

    private function formatValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_null($value)) {
            return 'null';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return var_export($value, true);
    }

    private function pruneEmptyBranches(array $translations): array
    {
        foreach ($translations as $key => $value) {
            if (is_array($value)) {
                $translations[$key] = $this->pruneEmptyBranches($value);

                if ($translations[$key] === []) {
                    unset($translations[$key]);
                }
            }
        }

        return $translations;
    }
}
