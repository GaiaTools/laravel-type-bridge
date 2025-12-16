<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Adapters;

use GaiaTools\TypeBridge\Contracts\TranslationSyntaxAdapter;

/**
 * PassthroughSyntaxAdapter
 *
 * Performs a minimal transformation from Laravel's translation syntax
 * to a plain JavaScript-friendly object format:
 * - Replace parameter tokens ":param" with "{param}".
 * - Simplify Laravel pluralization strings by removing range markers
 *   (e.g., "{0}", "{1}", "[2,*]") and joining the parts with
 *   a single pipe separator " | ", also converting parameters inside
 *   to the curly-brace form.
 * - Recurses through nested arrays; non-string values are preserved.
 */
final class PassthroughSyntaxAdapter implements TranslationSyntaxAdapter
{
    public function transform(array $translations): array
    {
        return $this->transformRecursive($translations);
    }

    public function getTargetLibrary(): string
    {
        return 'passthrough';
    }

    /**
     * @param  array<mixed, mixed>  $data
     * @return array<mixed, mixed>
     */
    private function transformRecursive(array $data): array
    {
        $transformed = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $transformed[$key] = $this->transformRecursive($value);
            } elseif (is_string($value)) {
                $transformed[$key] = $this->transformString($value);
            } else {
                $transformed[$key] = $value;
            }
        }

        return $transformed;
    }

    private function transformString(string $value): string
    {
        // First convert Laravel parameter syntax :name -> {name}
        $converted = preg_replace('/:(\w+)/', '{$1}', $value) ?? $value;

        // If this is a pluralization string, remove Laravel range markers and
        // output a pipe-delimited sequence suitable for plain JS usage.
        if ($this->isPluralization($value)) {
            return $this->transformPluralization($converted);
        }

        return $converted;
    }

    private function isPluralization(string $value): bool
    {
        return str_contains($value, '|') || (bool) preg_match('/\{\d+}|\[\d+,\*]/', $value);
    }

    /**
     * Convert Laravel pluralization syntax into a pipe-delimited string.
     * Examples:
     *   "item|items" -> "item|items"
     *   "{0} None|{1} One|[2,*] {count}" -> "None|One|{count}"
     */
    private function transformPluralization(string $value): string
    {
        $parts = explode('|', $value);
        $out = [];

        foreach ($parts as $part) {
            $part = trim($part);
            // Remove Laravel condition markers at the start of the segment
            $part = preg_replace('/^\{\d+}\s*/', '', $part) ?? $part;   // {0}, {1}
            $part = preg_replace('/^\[\d+,\*]\s*/', '', $part) ?? $part; // [2,*]
            // Ensure parameter tokens are in {param} form (already applied, but safe here)
            $part = preg_replace('/:(\w+)/', '{$1}', $part) ?? $part;
            $out[] = $part;
        }

        return implode('|', $out);
    }
}
