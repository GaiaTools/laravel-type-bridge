<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use GaiaTools\TypeBridge\Support\TranslationResolver;
use PHPUnit\Framework\TestCase;

final class TranslationResolverTest extends TestCase
{
    private function helper(): object
    {
        return new class
        {
            use TranslationResolver {
                shortClassName as public;
                hoistEnumKey as public;
                normalizeClassLikeKeys as public;
                dotFlatten as public;
            }
        };
    }

    public function test_short_class_name(): void
    {
        $h = $this->helper();
        self::assertSame('User', $h->shortClassName('App\\Models\\User'));
        self::assertSame('User', $h->shortClassName('\\App\\Models\\User'));
        self::assertSame('Plain', $h->shortClassName('Plain'));
    }

    public function test_hoist_enum_key(): void
    {
        $h = $this->helper();
        $input = [
            'enums' => [
                'Status' => [
                    'draft' => 'Draft',
                ],
            ],
            'other' => 'x',
        ];

        $actual = $h->hoistEnumKey($input);
        self::assertArrayHasKey('Status', $actual);
        self::assertArrayHasKey('other', $actual);
        self::assertSame('x', $actual['other']);
        self::assertSame(['draft' => 'Draft'], $actual['Status']);
        self::assertCount(2, $actual);
    }

    public function test_normalize_class_like_keys(): void
    {
        $h = $this->helper();

        $input = [
            'App\\Enums\\Status' => [
                'draft' => 'Draft',
            ],
            'nested' => [
                '\\App\\Models\\User' => 'User',
            ],
        ];

        $expected = [
            'Status' => [
                'draft' => 'Draft',
            ],
            'nested' => [
                'User' => 'User',
            ],
        ];

        self::assertSame($expected, $h->normalizeClassLikeKeys($input));
    }

    public function test_dot_flatten_handles_objects(): void
    {
        $h = $this->helper();

        $stringable = new class
        {
            public function __toString(): string
            {
                return 'ok';
            }
        };

        $nonStringable = new class
        {
            public int $x = 1;
        };

        $input = [
            'a' => [
                'b' => 1,
                'c' => $stringable,
                'd' => $nonStringable,
            ],
        ];

        $flat = $h->dotFlatten($input);

        self::assertSame(1, $flat['a.b']);
        self::assertSame('ok', $flat['a.c']);
        self::assertNull($flat['a.d']);
    }
}
