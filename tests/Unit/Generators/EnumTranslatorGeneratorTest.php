<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\Generators;

use GaiaTools\TypeBridge\Config\EnumTranslatorDiscoveryConfig;
use GaiaTools\TypeBridge\Config\GeneratorConfig;
use GaiaTools\TypeBridge\Discoverers\EnumTranslatorDiscoverer;
use GaiaTools\TypeBridge\Generators\EnumTranslatorGenerator;
use GaiaTools\TypeBridge\OutputFormatters\EnumTranslator\TsEnumTranslatorFormatter;
use GaiaTools\TypeBridge\Support\EnumTokenParser;
use GaiaTools\TypeBridge\Tests\TestCase;
use GaiaTools\TypeBridge\Transformers\EnumTranslatorTransformer;
use GaiaTools\TypeBridge\Writers\GeneratedFileWriter;
use PHPUnit\Framework\Attributes\Test;

class EnumTranslatorGeneratorTest extends TestCase
{
    #[Test]
    public function it_returns_correct_name(): void
    {
        $discoveryConfig = new EnumTranslatorDiscoveryConfig(
            discoveryPaths: [],
            excludes: [],
            outputPath: 'js/composables/generated',
            utilsComposablesPath: 'js/composables',
            utilsLibPath: 'js/lib'
        );

        $discoverer = new EnumTranslatorDiscoverer($discoveryConfig, new EnumTokenParser);
        $generatorConfig = GeneratorConfig::fromConfig();
        $transformer = new EnumTranslatorTransformer($discoveryConfig, $generatorConfig);
        $formatter = new TsEnumTranslatorFormatter('vue-i18n');
        $writer = new GeneratedFileWriter;

        $generator = new EnumTranslatorGenerator($discoverer, $transformer, $formatter, $writer);

        $this->assertSame('enum-translators', $generator->getName());
    }

    #[Test]
    public function it_generates_enum_translator_files(): void
    {
        $discoveryConfig = new EnumTranslatorDiscoveryConfig(
            discoveryPaths: [__DIR__.'/../../Fixtures/Enums'],
            excludes: [],
            outputPath: 'js/composables/generated',
            utilsComposablesPath: 'js/composables',
            utilsLibPath: 'js/lib'
        );

        $discoverer = new EnumTranslatorDiscoverer($discoveryConfig, new EnumTokenParser);
        $generatorConfig = GeneratorConfig::fromConfig();
        $transformer = new EnumTranslatorTransformer($discoveryConfig, $generatorConfig);
        $formatter = new TsEnumTranslatorFormatter('vue-i18n');
        $writer = new GeneratedFileWriter;

        $generator = new EnumTranslatorGenerator($discoverer, $transformer, $formatter, $writer);

        $files = $generator->generate();

        $this->assertGreaterThan(0, $files->count());
    }
}
