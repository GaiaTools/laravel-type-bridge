<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\Transformers;

use GaiaTools\TypeBridge\Tests\Fixtures\Enums\TestGrouped;
use GaiaTools\TypeBridge\Tests\Fixtures\Enums\TestPriority;
use GaiaTools\TypeBridge\Tests\Fixtures\Enums\TestStatus;
use GaiaTools\TypeBridge\Tests\TestCase;
use GaiaTools\TypeBridge\Transformers\EnumTransformer;
use GaiaTools\TypeBridge\ValueObjects\EnumCase;
use GaiaTools\TypeBridge\ValueObjects\EnumGroup;
use GaiaTools\TypeBridge\ValueObjects\EnumGroupValue;
use GaiaTools\TypeBridge\ValueObjects\TransformedEnum;
use PHPUnit\Framework\Attributes\Test;
use ReflectionEnum;

class EnumTransformerTest extends TestCase
{
    private EnumTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();

        $config = self::createGeneratorConfig();

        $this->transformer = new EnumTransformer($config);
    }

    #[Test]
    public function it_transforms_enum_to_value_object(): void
    {
        $reflection = new ReflectionEnum(TestStatus::class);

        $result = $this->transformer->transform($reflection);

        $this->assertInstanceOf(TransformedEnum::class, $result);
        $this->assertEquals('TestStatus', $result->name);
        $this->assertEquals('GaiaTools\\TypeBridge\\Tests\\Fixtures\\Enums', $result->namespace);
        $this->assertCount(3, $result->cases);
    }

    #[Test]
    public function it_extracts_enum_cases_correctly(): void
    {
        $reflection = new ReflectionEnum(TestStatus::class);

        $result = $this->transformer->transform($reflection);

        $this->assertContainsOnlyInstancesOf(EnumCase::class, $result->cases);

        $firstCase = $result->cases->first();
        $this->assertEquals('ACTIVE', $firstCase->name);
        $this->assertEquals('active', $firstCase->value);
        $this->assertNull($firstCase->docComment);
    }

    #[Test]
    public function it_extracts_doc_comments(): void
    {
        $reflection = new ReflectionEnum(TestPriority::class);

        $result = $this->transformer->transform($reflection);

        $highCase = $result->cases->firstWhere('name', 'HIGH');
        $this->assertNotNull($highCase);
        $this->assertStringContainsString('High priority', $highCase->docComment);
    }

    #[Test]
    public function it_handles_integer_backed_enums(): void
    {
        $reflection = new ReflectionEnum(TestPriority::class);

        $result = $this->transformer->transform($reflection);

        $this->assertContainsOnlyInstancesOf(EnumCase::class, $result->cases);

        $firstCase = $result->cases->first();
        $this->assertIsInt($firstCase->value);
        $this->assertEquals(1, $firstCase->value);
    }

    #[Test]
    public function it_throws_exception_when_required_comments_missing(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/missing a doc comment/');

        $reflection = new \ReflectionEnum(\GaiaTools\TypeBridge\Tests\Fixtures\Enums\TestNoComments::class);

        // This should throw because requiresComments=true and cases lack doc comments
        $this->transformer->transform($reflection);
    }

    #[Test]
    public function it_extracts_group_definitions_from_static_methods(): void
    {
        $reflection = new ReflectionEnum(TestGrouped::class);

        $result = $this->transformer->transform($reflection);

        $this->assertInstanceOf(TransformedEnum::class, $result);
        $this->assertCount(3, $result->groups);

        /** @var EnumGroup $arrayGroup */
        $arrayGroup = $result->groups->firstWhere('name', 'ArrayGroup');
        $this->assertSame('array', $arrayGroup->kind);
        $this->assertCount(4, $arrayGroup->values);
        $this->assertInstanceOf(EnumGroupValue::class, $arrayGroup->values[0]);

        $this->assertSame('enum', $arrayGroup->values[0]->kind);
        $this->assertSame('ALPHA', $arrayGroup->values[0]->value);
        $this->assertSame('literal', $arrayGroup->values[3]->kind);
        $this->assertSame('extra', $arrayGroup->values[3]->value);

        /** @var EnumGroup $recordGroup */
        $recordGroup = $result->groups->firstWhere('name', 'RecordGroup');
        $this->assertSame('record', $recordGroup->kind);
        $this->assertArrayHasKey('ALPHA', $recordGroup->values);
        $this->assertSame('enum', $recordGroup->values['ALPHA']->kind);
        $this->assertSame('ALPHA', $recordGroup->values['ALPHA']->value);
        $this->assertSame('literal', $recordGroup->values['custom']->kind);
        $this->assertSame('custom-value', $recordGroup->values['custom']->value);

        /** @var EnumGroup $enumCaseGroup */
        $enumCaseGroup = $result->groups->firstWhere('name', 'EnumCaseGroup');
        $this->assertSame('record', $enumCaseGroup->kind);
        $this->assertSame('enum', $enumCaseGroup->values['ALPHA']->kind);
        $this->assertSame('ALPHA', $enumCaseGroup->values['ALPHA']->value);
        $this->assertSame('BETA', $enumCaseGroup->values['BETA']->value);
    }
}
