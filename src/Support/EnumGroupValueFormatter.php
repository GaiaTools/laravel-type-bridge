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
        if (is_string($value)) { return StringQuoter::quoteJs($value); }
        if (is_bool($value)) { return $value ? 'true' : 'false'; }
        if ($value === null) { return 'null'; }
        return (string) $value;
    }
}
