<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\Config;

use GaiaTools\TypeBridge\Config\TranslationDiscoveryConfig;
use GaiaTools\TypeBridge\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TranslationDiscoveryConfigTest extends TestCase
{
    #[Test]
    public function it_returns_default_lang_paths(): void
    {
        $cfg = TranslationDiscoveryConfig::fromConfig();

        $this->assertIsArray($cfg->langPaths);
        $this->assertNotEmpty($cfg->langPaths);
        // Should include the base_path('lang') if it exists in this environment
        $expected = base_path('lang');
        if (is_dir($expected)) {
            $this->assertContains($expected, $cfg->langPaths);
        }
    }
}
