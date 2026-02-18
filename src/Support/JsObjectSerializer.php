<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Support;

final class JsObjectSerializer
{
    private const INDENT = '  ';

    /**
     * @param  array<string, mixed>  $data
     */
    public static function serializeObject(array $data, int $level = 0, bool $trailingComma = false): string
    {
        $indent = str_repeat(self::INDENT, $level);
        $lines = ['{'];
        $lastIndex = count($data) - 1;
        $keyQuoteStyle = self::resolveQuoteStyle();
        $allowUnquotedKeys = self::resolveAvoidQuotes();
        $i = 0;
        foreach ($data as $key => $value) {
            $keyEncoded = self::serializeKey($key, $keyQuoteStyle, $allowUnquotedKeys);
            $valueSerialized = self::serializeValue($value, $level + 1, $trailingComma);
            $comma = ($i === $lastIndex && ! $trailingComma) ? '' : ',';
            $lines[] = str_repeat(self::INDENT, $level + 1).$keyEncoded.': '.$valueSerialized.$comma;
            $i++;
        }
        $lines[] = $indent.'}';

        return implode("\n", $lines);
    }

    private static function serializeValue(mixed $value, int $level, bool $trailingComma = false): string
    {
        $out = '';

        if (is_string($value)) {
            $out = self::serializeString($value);
        } elseif (is_int($value) || is_float($value)) {
            $out = self::serializeNumber($value);
        } elseif (is_bool($value)) {
            $out = self::serializeBool($value);
        } elseif ($value === null) {
            $out = self::serializeNull();
        } elseif (is_array($value)) {
            $out = self::serializeArray($value, $level, $trailingComma);
        } else {
            $out = self::serializeFallback($value);
        }

        return $out;
    }

    private static function serializeString(string $value): string
    {
        return StringQuoter::quoteJs($value);
    }

    private static function serializeNumber(int|float $value): string
    {
        return (string) $value;
    }

    private static function serializeBool(bool $value): string
    {
        return $value ? 'true' : 'false';
    }

    private static function serializeNull(): string
    {
        return 'null';
    }

    /**
     * @param  array<mixed, mixed>  $value
     */
    private static function serializeArray(array $value, int $level, bool $trailingComma = false): string
    {
        if (self::isAssoc($value)) {
            /** @var array<string, mixed> $assoc */
            $assoc = $value;

            return self::serializeObject($assoc, $level, $trailingComma);
        }

        return self::serializeList($value, $level, $trailingComma);
    }

    /**
     * @param  array<int, mixed>  $list
     */
    private static function serializeList(array $list, int $level, bool $trailingComma = false): string
    {
        if ($list === []) {
            return '[]';
        }

        $items = [];
        foreach ($list as $item) {
            $items[] = self::serializeValue($item, $level + 1, $trailingComma);
        }

        $indent = self::indent($level);
        $itemIndent = self::indent($level + 1);

        $trailing = $trailingComma ? ',' : '';

        return "[\n"
            .$itemIndent.implode(",\n".$itemIndent, $items).$trailing."\n"
            .$indent.']';
    }

    private static function serializeFallback(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'null';
    }

    private static function serializeKey(string|int|float $key, string $quoteStyle, bool $allowUnquotedKeys): string
    {
        if (is_int($key) || is_float($key)) {
            return (string) $key;
        }

        $keyString = (string) $key;

        if ($allowUnquotedKeys && self::isValidIdentifier($keyString)) {
            return $keyString;
        }

        $normalizedStyle = self::normalizeQuoteStyle($quoteStyle);

        return StringQuoter::quoteJsWithStyle($keyString, $normalizedStyle);
    }

    private static function isValidIdentifier(string $key): bool
    {
        return (bool) preg_match('/^[A-Za-z_$][A-Za-z0-9_$]*$/', $key);
    }

    private static function normalizeQuoteStyle(string $style): string
    {
        return $style === 'single' ? 'single' : 'double';
    }

    private static function resolveQuoteStyle(): string
    {
        $style = config('type-bridge.quote_style', 'double');

        if (is_string($style)) {
            return $style;
        }

        return 'double';
    }

    private static function resolveAvoidQuotes(): bool
    {
        return config()->boolean('type-bridge.avoid_quotes', false);
    }

    private static function indent(int $level): string
    {
        return str_repeat(self::INDENT, $level);
    }

    /**
     * @param  array<mixed, mixed>  $arr
     */
    private static function isAssoc(array $arr): bool
    {
        if ($arr === []) {
            return false;
        }

        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
