<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Support;

use GaiaTools\TypeBridge\ValueObjects\EnumGroupValue;

final class EnumGroupValueFormatter
{
    public static function format(EnumGroupValue $value, string $enumName): string
    {
        if ($value->kind === EnumGroupValue::KIND_ENUM) {
            return $enumName.'.'.(string) $value->value;
        }

        return self::formatLiteral($value->value);
    }

    private static function formatLiteral(string|int|float|bool|null $value): string
    {
        $formatted = match (true) {
            is_string($value) => StringQuoter::quoteJs($value),
            is_bool($value) => $value ? 'true' : 'false',
            $value === null => 'null',
            default => (string) $value,
        };

        return $formatted;
    }
}
