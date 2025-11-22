<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Adapters;

use GaiaTools\TypeBridge\Contracts\TranslationSyntaxAdapter;

/**
 * Adapter for i18next (vanilla) and react-i18next
 * Both use the same syntax
 */
final class I18nextSyntaxAdapter implements TranslationSyntaxAdapter
{
    public function transform(array $translations): array
    {
        return $this->transformRecursive($translations);
    }

    public function getTargetLibrary(): string
    {
        return 'i18next';
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
                // Check if this is a pluralization that needs splitting
                if ($this->isPluralization($value)) {
                    // Pluralization creates multiple keys
                    $plurals = $this->transformPluralization($key, $value);
                    $transformed = array_merge($transformed, $plurals);
                } else {
                    $transformed[$key] = $this->transformString($value);
                }
            } else {
                $transformed[$key] = $value;
            }
        }

        return $transformed;
    }

    private function transformString(string $value): string
    {
        // Transform Laravel parameter syntax to i18next
        // :name → {{name}}
        // :attribute → {{attribute}}
        return preg_replace('/:(\w+)/', '{{$1}}', $value) ?? $value;
    }

    private function isPluralization(string $value): bool
    {
        return str_contains($value, '|') || preg_match('/\{\d+}|\[\d+,\*]/', $value);
    }

    /**
     * Transform Laravel pluralization to i18next plural keys
     *
     * @return array<string, string>
     */
    private function transformPluralization(string $baseKey, string $value): array
    {
        // Laravel: '{0} There are none|{1} There is one|[2,*] There are :count'
        // i18next uses separate keys with suffixes:
        // 'key_zero': 'There are none',
        // 'key_one': 'There is one',
        // 'key_other': 'There are {{count}}'
        //
        // Full i18next plural forms: zero, one, two, few, many, other
        // Most languages only need: zero, one, other

        $parts = explode('|', $value);
        $pluralForms = [];

        foreach ($parts as $index => $part) {
            $part = trim($part);

            // Detect the plural form from Laravel syntax
            if (preg_match('/^\{0}\s*(.+)/', $part, $matches)) {
                $pluralForms["{$baseKey}_zero"] = $this->transformString($matches[1]);
            } elseif (preg_match('/^\{1}\s*(.+)/', $part, $matches)) {
                $pluralForms["{$baseKey}_one"] = $this->transformString($matches[1]);
            } elseif (preg_match('/^\[\d+,\*]\s*(.+)/', $part, $matches)) {
                $pluralForms["{$baseKey}_other"] = $this->transformString($matches[1]);
            } else {
                // Simple case: 'item|items'
                if ($index === 0) {
                    $pluralForms["{$baseKey}_one"] = $this->transformString($part);
                } else {
                    $pluralForms["{$baseKey}_other"] = $this->transformString($part);
                }
            }
        }

        return $pluralForms;
    }
}
