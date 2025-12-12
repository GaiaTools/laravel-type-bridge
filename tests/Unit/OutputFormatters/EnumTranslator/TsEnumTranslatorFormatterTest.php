<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\OutputFormatters\EnumTranslator;

use GaiaTools\TypeBridge\OutputFormatters\EnumTranslator\TsEnumTranslatorFormatter;
use GaiaTools\TypeBridge\Tests\TestCase;
use GaiaTools\TypeBridge\ValueObjects\TransformedEnumTranslator;
use PHPUnit\Framework\Attributes\Test;

class TsEnumTranslatorFormatterTest extends TestCase
{
    #[Test]
    public function it_formats_for_vue_i18n(): void
    {
        $formatter = new TsEnumTranslatorFormatter('vue-i18n');

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
        $formatter = new TsEnumTranslatorFormatter('i18next');

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
    public function it_formats_for_laravel(): void
    {
        $formatter = new TsEnumTranslatorFormatter('laravel');

        $transformed = new TransformedEnumTranslator(
            name: 'useStatusTranslator',
            enumName: 'Status',
            translationKey: 'enums.status',
            enumImportPath: '@/enums/generated/Status',
            outputPath: 'js/composables/generated'
        );

        $output = $formatter->format($transformed);

        $this->assertStringContainsString('import { Status }', $output);
        $this->assertStringContainsString('createTranslator', $output);
        $this->assertStringContainsString('createEnumTranslationMap', $output);
    }

    #[Test]
    public function it_formats_for_vanilla(): void
    {
        $formatter = new TsEnumTranslatorFormatter('vanilla');

        $transformed = new TransformedEnumTranslator(
            name: 'useStatusTranslator',
            enumName: 'Status',
            translationKey: 'enums.status',
            enumImportPath: '@/enums/generated/Status',
            outputPath: 'js/composables/generated'
        );

        $output = $formatter->format($transformed);

        $this->assertStringContainsString('import { Status }', $output);
        $this->assertStringContainsString('createTranslator', $output);
        $this->assertStringContainsString('createEnumTranslationMap', $output);
    }

    #[Test]
    public function it_returns_ts_extension(): void
    {
        $formatter = new TsEnumTranslatorFormatter('vue-i18n');

        $this->assertSame('ts', $formatter->getExtension());
    }
}
