<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Support;

final class JsObjectSerializer
{
    private const INDENT = '  ';

    /**
     * @param  array<string, mixed>  $data
     */
    public static function serializeObject(array $data, int $level = 0): string
    {
        $indent = str_repeat(self::INDENT, $level);
        $lines = ['{'];
        $lastIndex = count($data) - 1;
        $i = 0;
        foreach ($data as $key => $value) {
            $keyEncoded = json_encode($key, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $valueSerialized = self::serializeValue($value, $level + 1);
            $comma = $i === $lastIndex ? '' : ',';
            $lines[] = str_repeat(self::INDENT, $level + 1).$keyEncoded.': '.$valueSerialized.$comma;
            $i++;
        }
        $lines[] = $indent.'}';

        return implode("\n", $lines);
    }

    private static function serializeValue(mixed $value, int $level): string
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
            $out = self::serializeArray($value, $level);
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
    private static function serializeArray(array $value, int $level): string
    {
        if (self::isAssoc($value)) {
            /** @var array<string, mixed> $assoc */
            $assoc = $value;

            return self::serializeObject($assoc, $level);
        }

        return self::serializeList($value, $level);
    }

    /**
     * @param  array<int, mixed>  $list
     */
    private static function serializeList(array $list, int $level): string
    {
        if ($list === []) {
            return '[]';
        }

        $items = [];
        foreach ($list as $item) {
            $items[] = self::serializeValue($item, $level + 1);
        }

        $indent = self::indent($level);
        $itemIndent = self::indent($level + 1);

        return "[\n"
            .$itemIndent.implode(",\n".$itemIndent, $items)."\n"
            .$indent.']';
    }

    private static function serializeFallback(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'null';
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
