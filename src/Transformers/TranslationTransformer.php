<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Transformers;

use GaiaTools\TypeBridge\Config\GeneratorConfig;
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
        $langDir = base_path('lang'.DIRECTORY_SEPARATOR.$locale);
        if (! File::isDirectory($langDir)) {
            throw new InvalidArgumentException(sprintf('Locale directory not found: %s', $langDir));
        }

        /** @var Collection<int, SplFileInfo> $files */
        $files = collect(File::files($langDir))
            ->filter(fn (SplFileInfo $file) => str_ends_with($file->getFilename(), '.php'))
            ->values();

        $merged = [];
        foreach ($files as $file) {
            $group = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            $data = require $file->getPathname();

            if (is_array($data)) {
                // Hoist nested grouping keys within the file (e.g., "enums")
                if (isset($data['enums']) && is_array($data['enums'])) {
                    foreach ($data['enums'] as $key => $value) {
                        $data[$key] = $value;
                    }
                    unset($data['enums']);
                }

                // Special handling: hoist "enums" file contents to root level
                if ($group === 'enums') {
                    $merged = array_merge($merged, $data);
                } else {
                    // Keep file-based grouping for other files
                    $merged[$group] = $data;
                }
            }
        }

        return $merged;
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
