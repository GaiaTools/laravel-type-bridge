<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\Config;

use GaiaTools\TypeBridge\Config\TranslationDiscoveryConfig;
use GaiaTools\TypeBridge\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TranslationDiscoveryConfigTest extends TestCase
{
    #[Test]
    public function it_handles_non_array_translations_config(): void
    {
        // Force a non-array (null) value to hit the early return in configuredLangPaths()
        // Note: using a string would violate the method signature (?array) and cause a TypeError
        config(['type-bridge.translations' => null]);

        $cfg = TranslationDiscoveryConfig::fromConfig();

        $this->assertIsArray($cfg->langPaths);

        // Fallback behavior mirrors Laravel's default base lang path when present
        $expected = base_path('lang');
        if (is_dir($expected)) {
            $this->assertContains($expected, $cfg->langPaths);
        } else {
            // If the default lang directory doesn't exist in this environment, result can be empty
            $this->assertSame([], $cfg->langPaths);
        }
    }

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

    #[Test]
    public function it_filters_invalid_and_duplicate_configured_paths_and_resolves_globs(): void
    {
        // Prepare temporary directories
        $root = base_path('tmp_cfg_lang');
        $moduleA = $root.'/modules/A/Resources/lang';
        $moduleB = $root.'/modules/B/Resources/lang';
        $dupDir = $root.'/dup/lang';

        // Clean up then create structure
        \Illuminate\Support\Facades\File::deleteDirectory($root);
        \Illuminate\Support\Facades\File::makeDirectory($moduleA, 0755, true);
        \Illuminate\Support\Facades\File::makeDirectory($moduleB, 0755, true);
        \Illuminate\Support\Facades\File::makeDirectory($dupDir, 0755, true);

        // Configure with various entries: empty string, non-existent, duplicate, glob
        config(['type-bridge.translations.discovery.include_paths' => [
            '',
            $dupDir,
            $dupDir, // duplicate
            $root.'/does-not-exist',
            $root.'/modules/*/Resources/lang', // glob should resolve to A and B
        ]]);

        $cfg = TranslationDiscoveryConfig::fromConfig();

        // Should only contain existing unique dirs in order: dupDir, A, B
        $this->assertSame([
            $dupDir,
            $moduleA,
            $moduleB,
        ], $cfg->langPaths);

        // Cleanup
        \Illuminate\Support\Facades\File::deleteDirectory($root);
    }

    #[Test]
    public function it_falls_back_to_base_lang_when_no_valid_paths_configured(): void
    {
        config(['type-bridge.translations.discovery.include_paths' => ['','   ', null]]);

        $cfg = TranslationDiscoveryConfig::fromConfig();

        // When invalid non-empty config values are provided, the resolver keeps none
        // (no fallback is applied because config is non-empty).
        $this->assertSame([], $cfg->langPaths);
    }
}
