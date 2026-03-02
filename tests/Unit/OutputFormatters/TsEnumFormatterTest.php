<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\OutputFormatters;

use GaiaTools\TypeBridge\OutputFormatters\Enum\TsEnumFormatter;
use GaiaTools\TypeBridge\Tests\TestCase;
use GaiaTools\TypeBridge\ValueObjects\EnumCase;
use GaiaTools\TypeBridge\ValueObjects\EnumGroup;
use GaiaTools\TypeBridge\ValueObjects\EnumGroupValue;
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

            groups: collect(),
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

            groups: collect(),
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

            groups: collect(),
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

            groups: collect(),
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

    #[Test]
    public function it_formats_group_exports_with_types(): void
    {
        $transformed = new TransformedEnum(
            name: 'Sample',
            cases: collect([
                new EnumCase('ALPHA', 'alpha'),
                new EnumCase('BETA', 'beta'),
            ]),
            namespace: 'App\\Enums',
            outputPath: resource_path('test-output/enums'),
            groups: collect([
                new EnumGroup('CustomerValues', EnumGroup::KIND_ARRAY, [
                    new EnumGroupValue(EnumGroupValue::KIND_ENUM, 'ALPHA'),
                    new EnumGroupValue(EnumGroupValue::KIND_LITERAL, 'extra'),
                ]),
                new EnumGroup('LoadValues', EnumGroup::KIND_RECORD, [
                    'ALPHA' => new EnumGroupValue(EnumGroupValue::KIND_ENUM, 'ALPHA'),
                    'custom' => new EnumGroupValue(EnumGroupValue::KIND_LITERAL, 'custom-value'),
                ]),
            ]),
        );

        $result = $this->formatter->format($transformed);

        $this->assertStringContainsString('export const CustomerValues = [', $result);
        $this->assertStringContainsString('Sample.ALPHA,', $result);
        $this->assertStringContainsString("'extra',", $result);
        $this->assertStringContainsString('export type CustomerValues = typeof CustomerValues[number];', $result);

        $this->assertStringContainsString('export const LoadValues = {', $result);
        $this->assertStringContainsString('ALPHA: Sample.ALPHA,', $result);
        $this->assertStringContainsString("'custom-value',", $result);
        $this->assertStringContainsString(
            'export type LoadValues = typeof LoadValues[keyof typeof LoadValues];',
            $result
        );
    }

    #[Test]
    public function it_uses_double_quotes_when_value_contains_apostrophe(): void
    {
        $transformed = new TransformedEnum(
            name: 'Contraction',
            cases: collect([
                new EnumCase('DONT', "don't"),
            ]),
            namespace: 'App\\Enums',
            outputPath: resource_path('test-output/enums'),

            groups: collect(),
        );

        $result = $this->formatter->format($transformed);

        // Should use double quotes when value has an apostrophe
        $this->assertStringContainsString('DONT: "don\'t",', $result);
        // TS-specific endings should remain
        $this->assertStringContainsString('} as const;', $result);
        $this->assertStringContainsString('export type Contraction = typeof Contraction[keyof typeof Contraction];', $result);
        // Should not use single quotes around the value
        $this->assertStringNotContainsString("DONT: 'don't',", $result);
    }

    #[Test]
    public function it_uses_single_quotes_when_value_contains_double_quotes(): void
    {
        $transformed = new TransformedEnum(
            name: 'Speech',
            cases: collect([
                new EnumCase('QUOTE', 'she said "hi"'),
            ]),
            namespace: 'App\\Enums',
            outputPath: resource_path('test-output/enums'),

            groups: collect(),
        );

        $result = $this->formatter->format($transformed);

        // Should use single quotes and leave double quotes unescaped
        $this->assertStringContainsString("QUOTE: 'she said \"hi\"',", $result);
        $this->assertStringContainsString('} as const;', $result);
        $this->assertStringContainsString('export type Speech = typeof Speech[keyof typeof Speech];', $result);
        // Should not wrap with double quotes
        $this->assertStringNotContainsString('QUOTE: "she said \"hi\"",', $result);
    }

    #[Test]
    public function it_uses_single_quotes_and_escapes_apostrophes_when_value_contains_both_quotes(): void
    {
        $transformed = new TransformedEnum(
            name: 'Mixed',
            cases: collect([
                new EnumCase('BOTH', 'He said: "don\'t"'),
            ]),
            namespace: 'App\\Enums',
            outputPath: resource_path('test-output/enums'),

            groups: collect(),
        );

        $result = $this->formatter->format($transformed);

        // Use single quotes, escape apostrophe, keep double quotes as-is
        $this->assertStringContainsString("BOTH: 'He said: \"don\\'t\"',", $result);
    }

    #[Test]
    public function it_escapes_backslashes_when_needed(): void
    {
        $transformed = new TransformedEnum(
            name: 'Paths',
            cases: collect([
                new EnumCase('WIN', 'C:\\Temp'),
            ]),
            namespace: 'App\\Enums',
            outputPath: resource_path('test-output/enums'),

            groups: collect(),
        );

        $result = $this->formatter->format($transformed);

        // In TS string, backslashes must be escaped
        $this->assertStringContainsString("WIN: 'C:\\\\Temp',", $result);
    }

    #[Test]
    public function it_includes_trailing_comma_when_config_is_true(): void
    {
        config(['type-bridge.trailing_commas' => true]);

        $transformed = new TransformedEnum(
            name: 'Status',
            cases: collect([
                new EnumCase('ACTIVE', 'active'),
                new EnumCase('INACTIVE', 'inactive'),
            ]),
            namespace: 'App\\Enums',
            outputPath: resource_path('test-output/enums'),

            groups: collect(),
        );

        $result = $this->formatter->format($transformed);

        $this->assertStringContainsString("ACTIVE: 'active',", $result);
        $this->assertStringContainsString("INACTIVE: 'inactive',", $result);
    }

    #[Test]
    public function it_excludes_trailing_comma_when_config_is_false(): void
    {
        config(['type-bridge.trailing_commas' => false]);

        $transformed = new TransformedEnum(
            name: 'Status',
            cases: collect([
                new EnumCase('ACTIVE', 'active'),
                new EnumCase('INACTIVE', 'inactive'),
            ]),
            namespace: 'App\\Enums',
            outputPath: resource_path('test-output/enums'),

            groups: collect(),
        );

        $result = $this->formatter->format($transformed);

        $this->assertStringContainsString("ACTIVE: 'active',", $result);
        $this->assertStringContainsString("INACTIVE: 'inactive'", $result);
        $this->assertStringNotContainsString("INACTIVE: 'inactive',", $result);
    }
}
