<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\Support;

use GaiaTools\TypeBridge\Support\EnumCaseIndex;
use GaiaTools\TypeBridge\Support\EnumGroupExtractor;
use GaiaTools\TypeBridge\Tests\Fixtures\Enums\TestGrouped;
use GaiaTools\TypeBridge\Tests\Fixtures\Enums\TestGroupedInt;
use GaiaTools\TypeBridge\Tests\Fixtures\Enums\TestGroupedInvalid;
use GaiaTools\TypeBridge\Tests\TestCase;
use GaiaTools\TypeBridge\ValueObjects\EnumGroup;
use GaiaTools\TypeBridge\ValueObjects\EnumGroupValue;
use PHPUnit\Framework\Attributes\Test;
use ReflectionEnum;

final class EnumGroupExtractorTest extends TestCase
{
    #[Test]
    public function it_returns_empty_collection_when_no_methods_are_provided(): void
    {
        $extractor = new EnumGroupExtractor;

        $groups = $extractor->extract(new ReflectionEnum(TestGrouped::class), []);

        $this->assertCount(0, $groups);
    }

    #[Test]
    public function it_extracts_groups_and_normalizes_values(): void
    {
        $extractor = new EnumGroupExtractor;

        $groups = $extractor
            ->extract(new ReflectionEnum(TestGrouped::class), ['arrayGroup', 'recordGroup', 'enumCaseGroup'])
            ->values()
            ->all();

        $this->assertSame('ArrayGroup', $groups[0]->name);
        $this->assertSame(EnumGroup::KIND_ARRAY, $groups[0]->kind);
        $this->assertSame(EnumGroupValue::KIND_ENUM, $groups[0]->values[0]->kind);
        $this->assertSame('ALPHA', $groups[0]->values[0]->value);
        $this->assertSame('BETA', $groups[0]->values[1]->value);
        $this->assertSame('GAMMA', $groups[0]->values[2]->value);
        $this->assertSame(EnumGroupValue::KIND_LITERAL, $groups[0]->values[3]->kind);
        $this->assertSame('extra', $groups[0]->values[3]->value);

        $this->assertSame('RecordGroup', $groups[1]->name);
        $this->assertSame(EnumGroup::KIND_RECORD, $groups[1]->kind);
        $this->assertSame(['ALPHA', 'custom', 'BETA'], array_keys($groups[1]->values));
        $this->assertSame('ALPHA', $groups[1]->values['ALPHA']->value);
        $this->assertSame(EnumGroupValue::KIND_LITERAL, $groups[1]->values['custom']->kind);
        $this->assertSame('custom-value', $groups[1]->values['custom']->value);
        $this->assertSame('BETA', $groups[1]->values['BETA']->value);

        $this->assertSame('EnumCaseGroup', $groups[2]->name);
        $this->assertSame(EnumGroup::KIND_RECORD, $groups[2]->kind);
        $this->assertSame(['ALPHA', 'BETA'], array_keys($groups[2]->values));
        $this->assertSame(EnumGroupValue::KIND_ENUM, $groups[2]->values['ALPHA']->kind);
        $this->assertSame('ALPHA', $groups[2]->values['ALPHA']->value);
        $this->assertSame(EnumGroupValue::KIND_ENUM, $groups[2]->values['BETA']->kind);
        $this->assertSame('BETA', $groups[2]->values['BETA']->value);
    }

    #[Test]
    public function it_matches_int_backed_and_named_enum_values(): void
    {
        $extractor = new EnumGroupExtractor;

        $groups = $extractor
            ->extract(new ReflectionEnum(TestGroupedInt::class), ['arrayGroup'])
            ->values()
            ->all();

        $this->assertSame(EnumGroup::KIND_ARRAY, $groups[0]->kind);
        $this->assertSame('ONE', $groups[0]->values[0]->value);
        $this->assertSame('TWO', $groups[0]->values[1]->value);
        $this->assertSame('TWO', $groups[0]->values[2]->value);
        $this->assertSame(EnumGroupValue::KIND_LITERAL, $groups[0]->values[3]->kind);
        $this->assertSame(1.5, $groups[0]->values[3]->value);
    }

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

    #[Test]
    public function it_throws_when_enum_record_contains_non_enum_values(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Enum group values must be enum cases to build object.');

        $extractor = new EnumGroupExtractor;
        $method = new \ReflectionMethod(EnumGroupExtractor::class, 'normalizeEnumCaseRecord');
        $method->setAccessible(true);

        $enumIndex = EnumCaseIndex::fromReflection(new ReflectionEnum(TestGrouped::class));

        $method->invoke(
            $extractor,
            [TestGrouped::ALPHA, 'not-an-enum'],
            $enumIndex
        );
    }
}
