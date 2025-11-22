<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\OutputFormatters;

use GaiaTools\TypeBridge\OutputFormatters\Enum\TsEnumFormatter;
use GaiaTools\TypeBridge\Tests\TestCase;
use GaiaTools\TypeBridge\ValueObjects\EnumCase;
use GaiaTools\TypeBridge\ValueObjects\TransformedEnum;
use PHPUnit\Framework\Attributes\Test;

class TsEnumFormatterTest extends TestCase
{
    private TsEnumFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new TsEnumFormatter;
    }

    #[Test]
    public function it_formats_enum_as_typescript_const(): void
    {
        $transformed = new TransformedEnum(
            name: 'Status',
            cases: collect([
                new EnumCase('ACTIVE', 'active'),
                new EnumCase('INACTIVE', 'inactive'),
            ]),
            namespace: 'App\\Enums',
            outputPath: resource_path('test-output/enums'),
        );

        $result = $this->formatter->format($transformed);

        $this->assertStringContainsString('export const Status = {', $result);
        $this->assertStringContainsString("ACTIVE: 'active',", $result);
        $this->assertStringContainsString("INACTIVE: 'inactive',", $result);
        $this->assertStringContainsString('} as const;', $result);
        $this->assertStringContainsString('export type Status = typeof Status[keyof typeof Status];', $result);
    }

    #[Test]
    public function it_includes_doc_comments(): void
    {
        $transformed = new TransformedEnum(
            name: 'Priority',
            cases: collect([
                new EnumCase('HIGH', 1, '/** High priority */'),
                new EnumCase('LOW', 2, '/** Low priority */'),
            ]),
            namespace: 'App\\Enums',
            outputPath: resource_path('test-output/enums'),
        );

        $result = $this->formatter->format($transformed);

        $this->assertStringContainsString('/** High priority */', $result);
        $this->assertStringContainsString('/** Low priority */', $result);
    }

    #[Test]
    public function it_handles_integer_values(): void
    {
        $transformed = new TransformedEnum(
            name: 'Level',
            cases: collect([
                new EnumCase('ONE', 1),
                new EnumCase('TWO', 2),
            ]),
            namespace: 'App\\Enums',
            outputPath: resource_path('test-output/enums'),
        );

        $result = $this->formatter->format($transformed);

        // Should not quote integers
        $this->assertStringContainsString('ONE: 1,', $result);
        $this->assertStringContainsString('TWO: 2,', $result);
        $this->assertStringNotContainsString("'1'", $result);
        $this->assertStringNotContainsString("'2'", $result);
    }

    #[Test]
    public function it_handles_string_values(): void
    {
        $transformed = new TransformedEnum(
            name: 'Status',
            cases: collect([
                new EnumCase('ACTIVE', 'active'),
            ]),
            namespace: 'App\\Enums',
            outputPath: resource_path('test-output/enums'),
        );

        $result = $this->formatter->format($transformed);

        // Should quote strings
        $this->assertStringContainsString("ACTIVE: 'active',", $result);
    }

    #[Test]
    public function it_returns_correct_extension(): void
    {
        $this->assertEquals('ts', $this->formatter->getExtension());
    }
}
