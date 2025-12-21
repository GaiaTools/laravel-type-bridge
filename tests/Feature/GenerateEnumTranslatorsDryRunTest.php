<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Feature;

use GaiaTools\TypeBridge\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class GenerateEnumTranslatorsDryRunTest extends TestCase
{
    #[Test]
    public function it_runs_dry_and_prints_table_and_summary(): void
    {
        // Limit discovery to fixtures to keep output deterministic
        config(['type-bridge.enum_translators.discovery.include_paths' => [__DIR__.'/../Fixtures/Enums']]);

        $this->artisan('type-bridge:enum-translators', ['--dry' => true])
            // Summary lines
            ->expectsOutputToContain('Checked')
            ->expectsOutputToContain('Eligible translations:')
            ->expectsOutputToContain('Not in FE generation set:')
            ->expectsOutputToContain('Ineligible translations:')
            // At least one discovered item should be printed in the table body
            ->expectsOutputToContain('TestStatusWithTranslator')
            ->assertExitCode(0);
    }
}
