<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\OutputFormatters;

use GaiaTools\TypeBridge\OutputFormatters\Enum\JsEnumFormatter;
use GaiaTools\TypeBridge\Tests\TestCase;
use GaiaTools\TypeBridge\ValueObjects\EnumCase;
use GaiaTools\TypeBridge\ValueObjects\TransformedEnum;
use PHPUnit\Framework\Attributes\Test;

class JsEnumFormatterTest extends TestCase
{
    private JsEnumFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new JsEnumFormatter;
    }

    #[Test]
    public function it_formats_enum_as_javascript_const(): void
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
        $this->assertStringContainsString('};', $result);

        // JS version should NOT have "as const" or type export
        $this->assertStringNotContainsString('as const', $result);
        $this->assertStringNotContainsString('export type', $result);
    }

    #[Test]
    public function it_returns_correct_extension(): void
    {
        $this->assertEquals('js', $this->formatter->getExtension());
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
        );

        $result = $this->formatter->format($transformed);

        // Should use double quotes when value has an apostrophe
        $this->assertStringContainsString('DONT: "don\'t",', $result);
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
        );

        $result = $this->formatter->format($transformed);

        // Should use single quotes and leave double quotes unescaped
        $this->assertStringContainsString("QUOTE: 'she said \"hi\"',", $result);
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
        );

        $result = $this->formatter->format($transformed);

        // In JS string, backslashes must be escaped
        $this->assertStringContainsString("WIN: 'C:\\\\Temp',", $result);
    }
}
