<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Feature;

use GaiaTools\TypeBridge\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class GenerateEnumTranslatorsCommandTest extends TestCase
{
    #[Test]
    public function it_generates_enum_translators_when_enabled(): void
    {
        config(['type-bridge.enum_translators.enabled' => true]);
        config(['type-bridge.enum_translators.discovery_paths' => [__DIR__.'/../Fixtures/Enums']]);

        $this->artisan('type-bridge:enum-translators')
            ->expectsOutputToContain('Generating enum translator composables...')
            ->expectsOutputToContain('Generated')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_skips_generation_when_disabled(): void
    {
        config(['type-bridge.enum_translators.enabled' => false]);

        $this->artisan('type-bridge:enum-translators')
            ->expectsOutputToContain('Enum translator generation is disabled.')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_generates_with_js_format_option(): void
    {
        config(['type-bridge.enum_translators.enabled' => true]);
        config(['type-bridge.enum_translators.discovery_paths' => [__DIR__.'/../Fixtures/Enums']]);

        $this->artisan('type-bridge:enum-translators', ['--format' => 'js'])
            ->expectsOutputToContain('Generating enum translator composables...')
            ->expectsOutputToContain('Generated')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_generates_with_ts_format_option(): void
    {
        config(['type-bridge.enum_translators.enabled' => true]);
        config(['type-bridge.enum_translators.discovery_paths' => [__DIR__.'/../Fixtures/Enums']]);

        $this->artisan('type-bridge:enum-translators', ['--format' => 'ts'])
            ->expectsOutputToContain('Generating enum translator composables...')
            ->expectsOutputToContain('Generated')
            ->assertExitCode(0);
    }
}
