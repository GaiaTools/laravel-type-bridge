<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Support;

final class IndentResolver
{
    private const DEFAULT_SPACES = 4;

    public static function unit(): string
    {
        return str_repeat(' ', self::spaces());
    }

    public static function repeat(int $level): string
    {
        return str_repeat(' ', self::spaces() * max(0, $level));
    }

    private static function spaces(): int
    {
        $value = config('type-bridge.indent_spaces', self::DEFAULT_SPACES);

        if (! is_int($value)) {
            return self::DEFAULT_SPACES;
        }

        return max(0, $value);
    }
}
