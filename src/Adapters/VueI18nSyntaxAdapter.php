<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Adapters;

use GaiaTools\TypeBridge\Contracts\TranslationSyntaxAdapter;

final class VueI18nSyntaxAdapter implements TranslationSyntaxAdapter
{
    public function transform(array $translations): array
    {
        return $this->transformRecursive($translations);
    }

    public function getTargetLibrary(): string
    {
        return 'vue-i18n';
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
        $transformed = preg_replace('/:(\w+)/', '{$1}', $value);

        if ($this->isPluralization($value)) {
            return $this->transformPluralization($value);
        }

        return $transformed ?? $value;
    }

    private function isPluralization(string $value): bool
    {
        return str_contains($value, '|') || preg_match('/\{\d+}|\[\d+,\*]/', $value);
    }

    /**
     * Transform Laravel pluralization to vue-i18n format
     * vue-i18n uses pipe-delimited strings: 'no apples | one apple | {count} apples'
     */
    private function transformPluralization(string $value): string
    {
        // Laravel: '{0} There are none|{1} There is one|[2,*] There are :count'
        // vue-i18n: 'There are none | There is one | There are {count}'
        $parts = explode('|', $value);
        $transformed = [];

        foreach ($parts as $part) {
            $part = trim($part);

            // Remove Laravel condition markers: {0}, {1}, [2,*]
            $part = preg_replace('/^\{\d+}\s*/', '', $part) ?? $part;
            $part = preg_replace('/^\[\d+,\*]\s*/', '', $part) ?? $part;

            // Transform :count to {count}
            $part = preg_replace('/:(\w+)/', '{$1}', $part) ?? $part;

            $transformed[] = $part;
        }

        return implode(' | ', $transformed);
    }
}
