<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Fixtures\Enums;

enum TestGroupedInvalid: string
{
    case ONE = 'one';

    public static function methodWithParam(string $value): array
    {
        return [$value];
    }

    public static function methodReturnsString(): string
    {
        return 'nope';
    }

    public static function methodWithInvalidValue(): array
    {
        return [new \stdClass];
    }

    protected static function hiddenGroup(): array
    {
        return [];
    }

    public function instanceGroup(): array
    {
        return [];
    }

    public static function testGroupedInvalid(): array
    {
        return [];
    }

    public static function foo_bar(): array
    {
        return [];
    }

    public static function fooBar(): array
    {
        return [];
    }
}
