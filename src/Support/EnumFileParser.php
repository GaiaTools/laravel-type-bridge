<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Support;

final class EnumFileParser
{
    /**
     * Parse a generated enum file (TS or JS) produced by this package and extract
     * the enum name and case keys and values.
     *
     * Returns an array with shape:
     *   [ 'name' => string, 'cases' => string[], 'entries' => array<string,string> ]
     * or null on failure.
     */
    public static function parseFile(string $path): ?array
    {
        if (! is_file($path)) {
            return null;
        }

        // Suppress warnings (e.g., permission denied). If unreadable, pass empty string to parser → null
        $contents = @file_get_contents($path);
        $contents = is_string($contents) ? $contents : '';

        return self::parseString($contents);
    }

    /**
     * @return array{name:string,cases:array<int,string>,entries:array<string,string>}|null
     */
    public static function parseString(string $contents): ?array
    {
        // Normalize newlines
        $contents = str_replace(["\r\n", "\r"], "\n", $contents);

        // Strip the generated header comment (starts with /** THIS FILE IS GENERATED ... */)
        // and any other block comments to simplify parsing.
        $contents = preg_replace('#/\*[\s\S]*?\*/#', '', $contents) ?? $contents;
        // Strip line comments
        $contents = preg_replace('#(^|\n)\s*//.*#', "$1", $contents) ?? $contents;

        // Match: export const Name = { ... } [as const];
        if (! preg_match('#export\s+const\s+(\w+)\s*=\s*\{([\s\S]*?)}\s*(?:as\s+const)?\s*;#m', $contents, $m)) {
            return null;
        }

        $name = $m[1];
        $body = $m[2];

        $cases = [];
        $entries = [];
        // Extract keys and values of the object entries: KEY: VALUE,
        // We capture until the trailing comma on the same line (generator emits one per entry).
        if (preg_match_all('#(^|\n)\s*([A-Za-z_]\w*)\s*:\s*([^,\n]+)\s*,?#', $body, $mm)) {
            foreach ($mm[2] as $idx => $key) {
                // Normalize and trim the captured value
                $rawValue = trim($mm[3][$idx]);
                if (! in_array($key, $cases, true)) {
                    $cases[] = $key;
                }
                // Keep the value as-is (already quoted/serialized in file)
                $entries[$key] = $rawValue;
            }
        }

        return [
            'name' => $name,
            'cases' => $cases,
            'entries' => $entries,
        ];
    }
}
