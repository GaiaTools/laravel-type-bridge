<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Fixtures\Enums;

use GaiaTools\TypeBridge\Attributes\GenerateEnum;

#[GenerateEnum(includeMethods: ['arrayGroup'])]
enum TestGroupedInt: int
{
    case ONE = 1;
    case TWO = 2;

    /** @return array<int, mixed> */
    public static function arrayGroup(): array
    {
        return [
            self::ONE,
            self::TWO->value,
            'TWO',
            1.5,
        ];
    }
}
