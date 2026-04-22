<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\OutputFormatters;

use GaiaTools\TypeBridge\OutputFormatters\Enum\JsEnumFormatter;
use GaiaTools\TypeBridge\OutputFormatters\Enum\TsEnumFormatter;
use GaiaTools\TypeBridge\Tests\TestCase;
use GaiaTools\TypeBridge\ValueObjects\EnumCase;
use GaiaTools\TypeBridge\ValueObjects\EnumGroup;
use GaiaTools\TypeBridge\ValueObjects\EnumGroupValue;
use GaiaTools\TypeBridge\ValueObjects\TransformedEnum;
use PHPUnit\Framework\Attributes\Test;

class EnumFormatterIndentTest extends TestCase
{
    #[Test]
    public function js_enum_formatter_defaults_to_four_space_indent(): void
    {
        $formatter = new JsEnumFormatter;
        $transformed = new TransformedEnum(
            name: 'Status',
            cases: collect([
                new EnumCase('ACTIVE', 'active'),
            ]),
            namespace: 'App\\Enums',
            outputPath: resource_path('test-output/enums'),
            groups: collect(),
        );

        $result = $formatter->format($transformed);

        $this->assertStringContainsString("\n    ACTIVE: 'active',", $result);
    }

    #[Test]
    public function js_enum_formatter_honors_two_space_indent(): void
    {
        config()->set('type-bridge.indent_spaces', 2);

        $formatter = new JsEnumFormatter;
        $transformed = new TransformedEnum(
            name: 'Status',
            cases: collect([
                new EnumCase('ACTIVE', 'active'),
            ]),
            namespace: 'App\\Enums',
            outputPath: resource_path('test-output/enums'),
            groups: collect(),
        );

        $result = $formatter->format($transformed);

        $this->assertStringContainsString("\n  ACTIVE: 'active',", $result);
        $this->assertStringNotContainsString("\n    ACTIVE: 'active',", $result);
    }

    #[Test]
    public function js_enum_formatter_honors_six_space_indent(): void
    {
        config()->set('type-bridge.indent_spaces', 6);

        $formatter = new JsEnumFormatter;
        $transformed = new TransformedEnum(
            name: 'Status',
            cases: collect([
                new EnumCase('ACTIVE', 'active'),
            ]),
            namespace: 'App\\Enums',
            outputPath: resource_path('test-output/enums'),
            groups: collect(),
        );

        $result = $formatter->format($transformed);

        $this->assertStringContainsString("\n      ACTIVE: 'active',", $result);
    }

    #[Test]
    public function ts_enum_formatter_honors_configured_indent_for_groups(): void
    {
        config()->set('type-bridge.indent_spaces', 2);

        $formatter = new TsEnumFormatter;
        $transformed = new TransformedEnum(
            name: 'Sample',
            cases: collect([
                new EnumCase('ALPHA', 'alpha'),
            ]),
            namespace: 'App\\Enums',
            outputPath: resource_path('test-output/enums'),
            groups: collect([
                new EnumGroup('SampleArr', EnumGroup::KIND_ARRAY, [
                    new EnumGroupValue(EnumGroupValue::KIND_ENUM, 'ALPHA'),
                ]),
            ]),
        );

        $result = $formatter->format($transformed);

        $this->assertStringContainsString("\n  Sample.ALPHA,", $result);
        $this->assertStringNotContainsString("\n    Sample.ALPHA,", $result);
    }

    #[Test]
    public function js_enum_formatter_falls_back_to_default_on_invalid_config(): void
    {
        config()->set('type-bridge.indent_spaces', 'nope');

        $formatter = new JsEnumFormatter;
        $transformed = new TransformedEnum(
            name: 'Status',
            cases: collect([
                new EnumCase('ACTIVE', 'active'),
            ]),
            namespace: 'App\\Enums',
            outputPath: resource_path('test-output/enums'),
            groups: collect(),
        );

        $result = $formatter->format($transformed);

        $this->assertStringContainsString("\n    ACTIVE: 'active',", $result);
    }
}
