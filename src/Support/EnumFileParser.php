<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Support;

final class EnumFileParser
{
    /**
     * Parse a generated enum file (TS or JS) produced by this package and extract
     * the enum name and case keys.
     *
     * Returns an array with shape [ 'name' => string, 'cases' => string[] ] or null on failure.
     */
    public static function parseFile(string $path): ?array
    {
        if (! is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            return null;
        }

        return self::parseString($contents);
    }

    /**
     * @return array{name:string,cases:array<int,string>}|null
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
        // Extract keys of the object entries: KEY: VALUE,
        if (preg_match_all('#(^|\n)\s*([A-Za-z_]\w*)\s*:\s*#', $body, $mm)) {
            foreach ($mm[2] as $key) {
                // Avoid duplicates if any
                if (! in_array($key, $cases, true)) {
                    $cases[] = $key;
                }
            }
        }

        return [
            'name' => $name,
            'cases' => $cases,
        ];
    }
}
