<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Fixtures\Enums;

use GaiaTools\TypeBridge\Attributes\GenerateEnum;

#[GenerateEnum(includeMethods: ['arrayGroup', 'recordGroup', 'enumCaseGroup'])]
enum TestGrouped: string
{
    case ALPHA = 'alpha';
    case BETA = 'beta';
    case GAMMA = 'gamma';

    /** @return array<int, mixed> */
    public static function arrayGroup(): array
    {
        return [
            self::ALPHA,
            self::BETA->value,
            'gamma',
            'extra',
        ];
    }

    /** @return array<string, mixed> */
    public static function recordGroup(): array
    {
        return [
            'ALPHA' => self::ALPHA,
            'custom' => 'custom-value',
            'BETA' => self::BETA->value,
        ];
    }

    /** @return array<int, self> */
    public static function enumCaseGroup(): array
    {
        return [
            self::ALPHA,
            self::BETA,
        ];
    }
}
