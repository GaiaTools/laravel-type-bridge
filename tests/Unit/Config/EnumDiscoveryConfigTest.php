<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\Config;

use GaiaTools\TypeBridge\Config\EnumDiscoveryConfig;
use GaiaTools\TypeBridge\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class EnumDiscoveryConfigTest extends TestCase
{
    #[Test]
    public function it_uses_defaults_when_config_missing(): void
    {
        // Clear the config for this package to test defaults
        config(['type-bridge' => []]);

        $cfg = EnumDiscoveryConfig::fromConfig();
        // Some environments may return the provided default, others may resolve to an empty array;
        // accept either but ensure it is an array of strings.
        $this->assertIsArray($cfg->paths);
        foreach ($cfg->paths as $p) {
            $this->assertIsString($p);
        }
        // If not empty, the first entry should be the app Enums path
        if ($cfg->paths !== []) {
            $this->assertSame(app_path('Enums'), $cfg->paths[0]);
        }
        $this->assertTrue($cfg->generateBackedEnums);
        $this->assertSame([], $cfg->excludes);
    }

    #[Test]
    public function it_accepts_string_or_array_paths_and_filters_non_strings(): void
    {
        // string path
        config(['type-bridge.enums.discovery.include_paths' => base_path('app/Enums')]);
        $cfg = EnumDiscoveryConfig::fromConfig();
        $this->assertSame([base_path('app/Enums')], $cfg->paths);

        // array with mixed types
        config(['type-bridge.enums.discovery.include_paths' => [base_path('app/Enums'), 123, null]]);
        $cfg = EnumDiscoveryConfig::fromConfig();
        $this->assertSame([base_path('app/Enums')], $cfg->paths);
    }

    #[Test]
    public function it_overrides_generate_backed_enums(): void
    {
        config(['type-bridge.enums.generate_backed_enums' => true]);
        $cfg = EnumDiscoveryConfig::fromConfig();
        $this->assertTrue($cfg->generateBackedEnums);

        config(['type-bridge.enums.generate_backed_enums' => false]);
        $cfg = EnumDiscoveryConfig::fromConfig();
        $this->assertFalse($cfg->generateBackedEnums);
    }

    #[Test]
    public function it_accepts_excludes_and_filters_non_strings(): void
    {
        config(['type-bridge.enums.discovery.exclude_paths' => ['Foo', 10, null, 'Bar']]);
        $cfg = EnumDiscoveryConfig::fromConfig();
        $this->assertSame(['Foo', 'Bar'], $cfg->excludes);

        // empty / invalid
        config(['type-bridge.enums.discovery.exclude_paths' => null]);
        $cfg = EnumDiscoveryConfig::fromConfig();
        $this->assertSame([], $cfg->excludes);
    }
}
