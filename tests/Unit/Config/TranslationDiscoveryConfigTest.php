<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\Config;

use GaiaTools\TypeBridge\Config\TranslationDiscoveryConfig;
use GaiaTools\TypeBridge\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TranslationDiscoveryConfigTest extends TestCase
{
    #[Test]
    public function it_returns_base_lang_path(): void
    {
        $cfg = TranslationDiscoveryConfig::fromConfig();

        $this->assertSame(base_path('lang'), $cfg->langPath);
    }
}
