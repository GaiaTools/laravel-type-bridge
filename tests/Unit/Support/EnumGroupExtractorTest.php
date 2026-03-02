<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\Support;

use GaiaTools\TypeBridge\Support\EnumGroupExtractor;
use GaiaTools\TypeBridge\Tests\Fixtures\Enums\TestGroupedInvalid;
use GaiaTools\TypeBridge\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use ReflectionEnum;

final class EnumGroupExtractorTest extends TestCase
{
    #[Test]
    public function it_throws_when_method_is_missing(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('missing method');

        $extractor = new EnumGroupExtractor;
        $extractor->extract(new ReflectionEnum(TestGroupedInvalid::class), ['missingMethod']);
    }

    #[Test]
    public function it_throws_when_method_has_parameters(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('must have no parameters');

        $extractor = new EnumGroupExtractor;
        $extractor->extract(new ReflectionEnum(TestGroupedInvalid::class), ['methodWithParam']);
    }

    #[Test]
    public function it_throws_when_method_is_not_public_static(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('must be public static');

        $extractor = new EnumGroupExtractor;
        $extractor->extract(new ReflectionEnum(TestGroupedInvalid::class), ['instanceGroup']);
    }

    #[Test]
    public function it_throws_when_method_return_is_not_array(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('must return an array');

        $extractor = new EnumGroupExtractor;
        $extractor->extract(new ReflectionEnum(TestGroupedInvalid::class), ['methodReturnsString']);
    }

    #[Test]
    public function it_throws_when_group_values_are_not_scalar_or_enum(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Enum group values must be scalar');

        $extractor = new EnumGroupExtractor;
        $extractor->extract(new ReflectionEnum(TestGroupedInvalid::class), ['methodWithInvalidValue']);
    }

    #[Test]
    public function it_throws_when_group_name_matches_enum_name(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('duplicate group name');

        $extractor = new EnumGroupExtractor;
        $extractor->extract(new ReflectionEnum(TestGroupedInvalid::class), ['testGroupedInvalid']);
    }

    #[Test]
    public function it_throws_when_group_names_collide(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('duplicate group name');

        $extractor = new EnumGroupExtractor;
        $extractor->extract(new ReflectionEnum(TestGroupedInvalid::class), ['foo_bar', 'fooBar']);
    }
}
