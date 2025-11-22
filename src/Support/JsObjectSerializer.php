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
        if (is_string($value)) {
            return StringQuoter::quoteJs($value);
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if ($value === null) {
            return 'null';
        }
        if (is_array($value)) {
            if (self::isAssoc($value)) {
                /** @var array<string, mixed> $assoc */
                $assoc = $value;

                return self::serializeObject($assoc, $level);
            }

            // List array
            $items = [];
            foreach ($value as $item) {
                $items[] = self::serializeValue($item, $level + 1);
            }
            if ($items === []) {
                return '[]';
            }
            $indent = str_repeat(self::INDENT, $level);
            $open = '[';
            $close = ']';
            $itemIndent = str_repeat(self::INDENT, $level + 1);

            return $open."\n".
                $itemIndent.implode(",\n".$itemIndent, $items)."\n".
                $indent.$close;
        }

        // Fallback to JSON encoding for other types
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'null';
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
