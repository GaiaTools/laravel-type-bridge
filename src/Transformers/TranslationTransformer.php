<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Transformers;

use GaiaTools\TypeBridge\Config\GeneratorConfig;
use GaiaTools\TypeBridge\Config\TranslationDiscoveryConfig;
use GaiaTools\TypeBridge\Contracts\Transformer;
use GaiaTools\TypeBridge\Contracts\TranslationSyntaxAdapter;
use GaiaTools\TypeBridge\ValueObjects\TransformedTranslation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use SplFileInfo;

final class TranslationTransformer implements Transformer
{
    public function __construct(
        private readonly GeneratorConfig $config,
        private readonly TranslationSyntaxAdapter $syntaxAdapter,
        private readonly ?TranslationDiscoveryConfig $discoveryConfig = null,
    ) {}

    /**
     * @param  array{locale: string, flat: bool}  $source
     */
    public function transform(mixed $source): TransformedTranslation
    {
        $locale = $source['locale'];
        $flat = $source['flat'];

        $data = $this->readAndMerge($locale);

        $data = $this->normalizeClassLikeKeys($data);

        $data = $this->syntaxAdapter->transform($data);

        $data = $flat ? $this->dotFlatten($data) : $data;

        $outputPath = resource_path($this->config->translationOutputPath);

        return new TransformedTranslation(
            locale: $locale,
            data: $data,
            isFlat: $flat,
            outputPath: $outputPath,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function readAndMerge(string $locale): array
    {
        $roots = $this->getLangRoots();

        $anyFound = false;
        $final = [];

        foreach ($roots as $root) {
            $langDir = $this->buildLocaleDir($root, $locale);
            if (! File::isDirectory($langDir)) {
                continue;
            }

            $anyFound = true;
            $current = $this->loadLocaleDir($langDir);

            // Merge this root into final; later roots override earlier ones
            $final = array_replace_recursive($final, $current);
        }

        if (! $anyFound) {
            throw new InvalidArgumentException('Locale directory not found for locale: '.$locale);
        }

        return $final;
    }

    /**
     * @return list<string>
     */
    private function getLangRoots(): array
    {
        return ($this->discoveryConfig ?? TranslationDiscoveryConfig::fromConfig())->langPaths;
    }

    private function buildLocaleDir(string $root, string $locale): string
    {
        return rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$locale;
    }

    /**
     * Load and merge translation arrays from a locale directory.
     * Special rule: contents of the "enums.php" file are hoisted to the root level.
     *
     * @return array<string, mixed>
     */
    private function loadLocaleDir(string $langDir): array
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
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function hoistEnumKey(array $data): array
    {
        if (isset($data['enums']) && is_array($data['enums'])) {
            foreach ($data['enums'] as $key => $value) {
                $data[$key] = $value;
            }
            unset($data['enums']);
        }

        return $data;
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
                $out[$newKey] = is_object($value) ? (method_exists($value, '__toString') ? (string) $value : null) : $value;
            }
        }
    }

    /**
     * Normalize top-level and nested keys by replacing any FQCN-like keys
     * (containing backslashes) with their short class name (last segment).
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
        // Trim leading backslashes and find last namespace separator
        $trimmed = ltrim($maybeFqcn, '\\');
        $pos = strrpos($trimmed, '\\');

        return $pos === false ? $trimmed : substr($trimmed, $pos + 1);
    }
}
