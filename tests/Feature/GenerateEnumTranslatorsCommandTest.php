<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Feature;

use GaiaTools\TypeBridge\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class GenerateEnumTranslatorsCommandTest extends TestCase
{
    #[Test]
    public function it_generates_with_js_format_option(): void
    {
        config(['type-bridge.enum_translators.discovery.include_paths' => [__DIR__.'/../Fixtures/Enums']]);

        $this->artisan('type-bridge:enum-translators', ['--format' => 'js'])
            ->expectsOutputToContain('Generating enum translator composables...')
            ->expectsOutputToContain('Generated')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_generates_with_ts_format_option(): void
    {
        config(['type-bridge.enum_translators.discovery.include_paths' => [__DIR__.'/../Fixtures/Enums']]);

        $this->artisan('type-bridge:enum-translators', ['--format' => 'ts'])
            ->expectsOutputToContain('Generating enum translator composables...')
            ->expectsOutputToContain('Generated')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_uses_default_format_when_option_missing(): void
    {
        // Ensure enabled and provide discovery path
        config(['type-bridge.enum_translators.discovery.include_paths' => [__DIR__.'/../Fixtures/Enums']]);

        // Set global default output format (as used by GeneratorConfig)
        config(['type-bridge.output_format' => 'ts']);

        // No --format option provided: should fall back to GeneratorConfig::outputFormat
        $this->artisan('type-bridge:enum-translators')
            ->expectsOutputToContain('Generating enum translator composables...')
            ->expectsOutputToContain('Generated')
            ->assertExitCode(0);
    }
}
