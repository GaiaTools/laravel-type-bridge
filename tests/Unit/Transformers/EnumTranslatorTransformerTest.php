<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\Transformers;

use GaiaTools\TypeBridge\Config\EnumTranslatorDiscoveryConfig;
use GaiaTools\TypeBridge\Config\GeneratorConfig;
use GaiaTools\TypeBridge\Tests\Fixtures\Enums\TestStatusWithTranslator;
use GaiaTools\TypeBridge\Tests\TestCase;
use GaiaTools\TypeBridge\Transformers\EnumTranslatorTransformer;
use GaiaTools\TypeBridge\ValueObjects\TransformedEnumTranslator;
use PHPUnit\Framework\Attributes\Test;
use ReflectionEnum;

class EnumTranslatorTransformerTest extends TestCase
{
    #[Test]
    public function it_transforms_enum_reflection_to_transformed_enum_translator(): void
    {
        $generatorConfig = GeneratorConfig::fromConfig();
        $discoveryConfig = new EnumTranslatorDiscoveryConfig(
            discoveryPaths: ['app/Enums'],
            excludes: [],
            outputPath: 'js/composables/generated',
            utilsComposablesPath: 'js/composables',
            utilsLibPath: 'js/lib'
        );

        $transformer = new EnumTranslatorTransformer($discoveryConfig, $generatorConfig);

        $reflection = new ReflectionEnum(TestStatusWithTranslator::class);
        $item = [
            'reflection' => $reflection,
            'translationKey' => 'enums.status',
        ];

        $result = $transformer->transform($item);

        $this->assertInstanceOf(TransformedEnumTranslator::class, $result);
        $this->assertSame('useTestStatusWithTranslatorTranslator', $result->name);
        $this->assertSame('TestStatusWithTranslator', $result->enumName);
        $this->assertSame('enums.status', $result->translationKey);
        $this->assertStringContainsString('@/enums/generated/', $result->enumImportPath);
        $this->assertStringContainsString('js/composables/generated', $result->outputPath);
    }

    #[Test]
    public function it_creates_composable_name_from_enum_name(): void
    {
        $generatorConfig = GeneratorConfig::fromConfig();
        $discoveryConfig = new EnumTranslatorDiscoveryConfig(
            discoveryPaths: ['app/Enums'],
            excludes: [],
            outputPath: 'js/composables/generated',
            utilsComposablesPath: 'js/composables',
            utilsLibPath: 'js/lib'
        );

        $transformer = new EnumTranslatorTransformer($discoveryConfig, $generatorConfig);

        $reflection = new ReflectionEnum(TestStatusWithTranslator::class);
        $item = [
            'reflection' => $reflection,
            'translationKey' => 'custom.key',
        ];

        $result = $transformer->transform($item);

        $this->assertStringStartsWith('use', $result->name);
        $this->assertStringEndsWith('Translator', $result->name);
    }
}
