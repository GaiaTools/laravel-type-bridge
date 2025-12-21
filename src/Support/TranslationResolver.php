<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use SplFileInfo;

/**
 * Reusable translation resolution helpers shared by TranslationTransformer and TranslationIndex.
 */
trait TranslationResolver
{
    protected function buildLocaleDir(string $root, string $locale): string
    {
        return rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$locale;
    }

    /**
     * Load and merge translation arrays from a locale directory.
     * Special rule: contents of the "enums.php" file are hoisted to the root level.
     *
     * @return array<string, mixed>
     */
    protected function loadLocaleDir(string $langDir): array
    {
        /** @var Collection<int, SplFileInfo> $files */
        $files = collect(File::files($langDir))
            ->filter(fn (SplFileInfo $file) => str_ends_with($file->getFilename(), '.php'))
            ->values();

        $current = [];
        foreach ($files as $file) {
            $group = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            $data = require $file->getPathname();

            if (! is_array($data)) {
                continue;
            }

            $data = $this->hoistEnumKey($data);

            if ($group === 'enums') {
                $current = array_merge($current, $data);

                continue;
            }

            $current[$group] = $data;
        }

        return $current;
    }

    /**
     * Hoist nested grouping key "enums" within a file's returned array.
     *
     * @param  array<mixed, mixed>  $data
     * @return array<string, mixed>
     */
    protected function hoistEnumKey(array $data): array
    {
        // Normalize keys to strings first
        $normalized = [];
        foreach ($data as $key => $value) {
            $normalized[(string) $key] = $value;
        }
        $data = $normalized;

        if (isset($data['enums']) && is_array($data['enums'])) {
            foreach ($data['enums'] as $key => $value) {
                $data[$key] = $value;
            }
            unset($data['enums']);
        }

        return $data;
    }

    /**
     * Normalize top-level and nested keys by replacing any FQCN-like keys
     * (containing backslashes) with their short class name (last segment).
     *
     * @param  array<mixed, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizeClassLikeKeys(array $data): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            $normalizedKey = is_string($key) ? $this->shortClassName($key) : (string) $key;
            if (is_array($value)) {
                $out[$normalizedKey] = $this->normalizeClassLikeKeys($value);
            } else {
                $out[$normalizedKey] = $value;
            }
        }

        return $out;
    }

    protected function shortClassName(string $maybeFqcn): string
    {
        // Trim leading backslashes and find last namespace separator
        $trimmed = ltrim($maybeFqcn, '\\');
        $pos = strrpos($trimmed, '\\');

        return $pos === false ? $trimmed : substr($trimmed, $pos + 1);
    }

    /**
     * @param  array<mixed, mixed>  $input
     * @return array<string, mixed>
     */
    protected function dotFlatten(array $input): array
    {
        $flat = [];
        $this->flattenRecursive($input, '', $flat);

        return $flat;
    }

    /**
     * @param  array<mixed, mixed>  $input
     * @param  array<string, mixed>  $out
     */
    protected function flattenRecursive(array $input, string $prefix, array &$out): void
    {
        foreach ($input as $key => $value) {
            $newKey = $prefix === '' ? (string) $key : $prefix.'.'.$key;
            if (is_array($value)) {
                $this->flattenRecursive($value, $newKey, $out);
            } else {
                if (is_object($value)) {
                    if (method_exists($value, '__toString')) {
                        $out[$newKey] = (string) $value;
                    } else {
                        $out[$newKey] = null;
                    }
                } else {
                    $out[$newKey] = $value;
                }
            }
        }
    }
}
