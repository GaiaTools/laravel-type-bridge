<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Support;

final class EnumGroupFileParser
{
    /**
     * @return array<string, array{kind:string, entries:array<string,string>}>
     */
    public static function parseFile(string $path, string $enumName): array
    {
        if (! is_file($path)) {
            return [];
        }

        $contents = @file_get_contents($path);
        $contents = is_string($contents) ? $contents : '';

        return self::parseString($contents, $enumName);
    }

    /**
     * @return array<string, array{kind:string, entries:array<string,string>}>
     */
    public static function parseString(string $contents, string $enumName): array
    {
        $clean = self::normalizeAndClean($contents);
        $matches = self::matchExports($clean);

        return self::parseMatches($matches, $enumName);
    }

    private static function normalizeAndClean(string $contents): string
    {
        $contents = str_replace(["\r\n", "\r"], "\n", $contents);
        $contents = preg_replace('#/\*[\s\S]*?\*/#', '', $contents) ?? $contents;
        $contents = preg_replace('#(^|\n)\s*//.*#', '$1', $contents) ?? $contents;

        return $contents;
    }

    /**
     * @return array<int, array{name:string, kind:string, body:string}>
     */
    private static function matchExports(string $contents): array
    {
        $pattern = '#export\s+const\s+(\w+)\s*=\s*(\{|\[)([\s\S]*?)(\}|\])\s*(?:as\s+const)?\s*;#m';
        if (! preg_match_all($pattern, $contents, $matches, PREG_SET_ORDER)) {
            return [];
        }

        return array_map(static fn (array $match) => [
            'name' => $match[1],
            'kind' => $match[2] === '[' ? 'array' : 'record',
            'body' => $match[3],
        ], $matches);
    }

    /**
     * @return array<string,string>
     */
    private static function extractArrayEntries(string $body): array
    {
        $entries = [];
        $lines = self::nonEmptyLines($body);

        foreach ($lines as $line) {
            $entries[(string) count($entries)] = self::trimTrailingComma($line);
        }

        return $entries;
    }

    /**
     * @return array<string,string>
     */
    private static function extractRecordEntries(string $body): array
    {
        $entries = [];
        foreach (self::nonEmptyLines($body) as $line) {
            $parsed = self::parseRecordLine($line);
            if ($parsed !== null) {
                $entries[$parsed['key']] = $parsed['value'];
            }
        }

        return $entries;
    }

    /**
     * @param  array<int, array{name:string, kind:string, body:string}>  $matches
     * @return array<string, array{kind:string, entries:array<string,string>}>
     */
    private static function parseMatches(array $matches, string $enumName): array
    {
        return array_reduce($matches, function (array $groups, array $match) use ($enumName) {
            if ($match['name'] === $enumName) {
                return $groups;
            }

            $entries = $match['kind'] === 'array'
                ? self::extractArrayEntries($match['body'])
                : self::extractRecordEntries($match['body']);

            $groups[$match['name']] = ['kind' => $match['kind'], 'entries' => $entries];

            return $groups;
        }, []);
    }

    /**
     * @return array<int,string>
     */
    private static function nonEmptyLines(string $body): array
    {
        $lines = array_map('trim', explode("\n", $body));

        return array_values(array_filter($lines, static fn (string $line) => $line !== ''));
    }

    /**
     * @return array{key:string,value:string}|null
     */
    private static function parseRecordLine(string $line): ?array
    {
        if (! preg_match('#^(.+?)\s*:\s*(.+)$#', $line, $match)) {
            return null;
        }

        return [
            'key' => self::normalizeKey($match[1]),
            'value' => self::trimTrailingComma($match[2]),
        ];
    }

    private static function normalizeKey(string $raw): string
    {
        $key = trim($raw);
        $quote = $key[0] ?? '';

        if (($quote === '"' || $quote === "'") && str_ends_with($key, $quote)) {
            $key = substr($key, 1, -1);
            $key = stripcslashes($key);
        }

        return $key;
    }

    private static function trimTrailingComma(string $value): string
    {
        $value = trim($value);

        return str_ends_with($value, ',') ? substr($value, 0, -1) : $value;
    }
}
