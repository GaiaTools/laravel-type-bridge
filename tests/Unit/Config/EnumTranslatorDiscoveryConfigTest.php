<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\Config;

use GaiaTools\TypeBridge\Config\EnumTranslatorDiscoveryConfig;
use GaiaTools\TypeBridge\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class EnumTranslatorDiscoveryConfigTest extends TestCase
{
    #[Test]
    public function it_uses_defaults_when_config_missing(): void
    {
        config()->offsetUnset('type-bridge.enum_translators');

        $cfg = EnumTranslatorDiscoveryConfig::fromConfig();

        $this->assertSame([app_path('Enums')], $cfg->discoveryPaths);
        $this->assertSame([], $cfg->excludes);
        $this->assertSame('js/composables/generated', $cfg->outputPath);
        $this->assertSame('js/composables', $cfg->utilsComposablesPath);
        $this->assertSame('js/lib', $cfg->utilsLibPath);
    }

    #[Test]
    public function it_reads_all_config_values(): void
    {
        config([
            'type-bridge.enum_translators.discovery.include_paths' => ['app/Models/Enums', 'app/Domain'],
            'type-bridge.enum_translators.discovery.exclude_paths' => ['TestEnum'],
            'type-bridge.enum_translators.translator_output_path' => 'frontend/composables',
            'type-bridge.enum_translators.utils_composables_output_path' => 'frontend/utils/composables',
            'type-bridge.enum_translators.utils_lib_output_path' => 'frontend/utils/lib',
        ]);

        $cfg = EnumTranslatorDiscoveryConfig::fromConfig();

        $this->assertSame(['app/Models/Enums', 'app/Domain'], $cfg->discoveryPaths);
        $this->assertSame(['TestEnum'], $cfg->excludes);
        $this->assertSame('frontend/composables', $cfg->outputPath);
        $this->assertSame('frontend/utils/composables', $cfg->utilsComposablesPath);
        $this->assertSame('frontend/utils/lib', $cfg->utilsLibPath);
    }
}
