<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\OutputFormatters\EnumTranslator;

use GaiaTools\TypeBridge\OutputFormatters\EnumTranslator\JsEnumTranslatorFormatter;
use GaiaTools\TypeBridge\Tests\TestCase;
use GaiaTools\TypeBridge\ValueObjects\TransformedEnumTranslator;
use PHPUnit\Framework\Attributes\Test;

class JsEnumTranslatorFormatterTest extends TestCase
{
    #[Test]
    public function it_formats_for_vue_i18n(): void
    {
        $formatter = new JsEnumTranslatorFormatter('vue-i18n');

        $transformed = new TransformedEnumTranslator(
            name: 'useStatusTranslator',
            enumName: 'Status',
            translationKey: 'enums.status',
            enumImportPath: '@/enums/generated/Status',
            outputPath: 'js/composables/generated'
        );

        $output = $formatter->format($transformed);

        $this->assertStringContainsString('import { Status }', $output);
        $this->assertStringContainsString("from '@/enums/generated/Status'", $output);
        $this->assertStringContainsString('useTranslator', $output);
        $this->assertStringContainsString('createEnumTranslationMap', $output);
        $this->assertStringContainsString('useStatusTranslator', $output);
        $this->assertStringContainsString("'enums.status'", $output);
    }

    #[Test]
    public function it_formats_for_i18next(): void
    {
        $formatter = new JsEnumTranslatorFormatter('i18next');

        $transformed = new TransformedEnumTranslator(
            name: 'useStatusTranslator',
            enumName: 'Status',
            translationKey: 'enums.status',
            enumImportPath: '@/enums/generated/Status',
            outputPath: 'js/composables/generated'
        );

        $output = $formatter->format($transformed);

        $this->assertStringContainsString('import { Status }', $output);
        $this->assertStringContainsString('useTranslator', $output);
        $this->assertStringContainsString('createEnumTranslationMap', $output);
    }


    #[Test]
    public function it_returns_js_extension(): void
    {
        $formatter = new JsEnumTranslatorFormatter('vue-i18n');

        $this->assertSame('js', $formatter->getExtension());
    }
}
