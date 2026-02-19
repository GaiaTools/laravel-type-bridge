<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Support;

final class EnumGroupKeyFormatter
{
    public static function format(string|int $key): string
    {
        if (is_int($key)) {
            return (string) $key;
        }

        if (self::isValidIdentifier($key)) {
            return $key;
        }

        return StringQuoter::quoteJsWithStyle($key, self::quoteStyle());
    }

    private static function quoteStyle(): string
    {
        $style = config('type-bridge.quote_style', 'double');

        return $style === 'single' ? 'single' : 'double';
    }

    private static function isValidIdentifier(string $key): bool
    {
        return (bool) preg_match('/^[A-Za-z_$][A-Za-z0-9_$]*$/', $key);
    }
}
