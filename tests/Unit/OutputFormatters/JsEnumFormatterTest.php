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
}
