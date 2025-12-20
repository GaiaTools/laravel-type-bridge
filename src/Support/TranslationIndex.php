<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Support;

use GaiaTools\TypeBridge\Config\TranslationDiscoveryConfig;
use Illuminate\Support\Facades\File;
use ReflectionEnum;
use SplFileInfo;
use UnitEnum;

/**
 * Lightweight translation index for existence checks against Laravel lang files.
 */
final class TranslationIndex
{
    /** @var array<string, mixed>|null */
    private ?array $flat = null;

    public function __construct(
        private readonly ?string $locale = null,
        private readonly ?TranslationDiscoveryConfig $config = null,
    ) {}

    /**
     * @param  ReflectionEnum<UnitEnum>  $enum
     */
    public function hasAnyForEnum(string $translationPrefix, ReflectionEnum $enum): bool
    {
        $flat = $this->flat() ?: [];

        foreach ($enum->getCases() as $case) {
            $key = $translationPrefix.'.'.$case->name;
            if (array_key_exists($key, $flat)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Load and cache a flattened view of translations for the chosen locale.
     *
     * @return array<string, mixed>
     */
    private function flat(): array
    {
        if ($this->flat !== null) {
            return $this->flat;
        }

        $locale = $this->locale ?? (string) (config('app.locale') ?: 'en');

        $roots = ($this->config ?? TranslationDiscoveryConfig::fromConfig())->langPaths;
        $merged = [];

        foreach ($roots as $root) {
            $dir = rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$locale;
            if (! File::isDirectory($dir)) {
                continue;
            }

            $current = $this->loadLocaleDir($dir);
            // Normalize class-like keys (e.g., FQCN) to short names to mirror TranslationTransformer
            $current = $this->normalizeClassLikeKeys($current);
            $merged = array_replace_recursive($merged, $current);
        }

        $this->flat = $this->dotFlatten($merged);

        return $this->flat;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadLocaleDir(string $langDir): array
    {
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
     * @param  array<mixed, mixed>  $data
     * @return array<string, mixed>
     */
    private function hoistEnumKey(array $data): array
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
     * Mirrors TranslationTransformer::normalizeClassLikeKeys.
     *
     * @param  array<mixed, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeClassLikeKeys(array $data): array
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

    private function shortClassName(string $maybeFqcn): string
    {
        $trimmed = ltrim($maybeFqcn, '\\');
        $pos = strrpos($trimmed, '\\');

        return $pos === false ? $trimmed : substr($trimmed, $pos + 1);
    }

    /**
     * @param  array<mixed, mixed>  $input
     * @return array<string, mixed>
     */
    private function dotFlatten(array $input): array
    {
        $flat = [];
        $this->flattenRecursive($input, '', $flat);

        return $flat;
    }

    /**
     * @param  array<mixed, mixed>  $input
     * @param  array<string, mixed>  $out
     */
    private function flattenRecursive(array $input, string $prefix, array &$out): void
    {
        foreach ($input as $key => $value) {
            $newKey = $prefix === '' ? (string) $key : $prefix.'.'.$key;
            if (is_array($value)) {
                $this->flattenRecursive($value, $newKey, $out);
            } else {
                $out[$newKey] = $value;
            }
        }
    }
}
