<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Feature;

use GaiaTools\TypeBridge\Tests\TestCase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;

final class PublishEnumTranslatorUtilsCommandTest extends TestCase
{
    #[Test]
    public function it_publishes_translator_utils_in_ts_format(): void
    {
        config(['type-bridge.output_format' => 'ts']);

        $this->artisan('type-bridge:publish-translator-utils')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_publishes_translator_utils_in_js_format(): void
    {
        config(['type-bridge.output_format' => 'js']);

        $this->artisan('type-bridge:publish-translator-utils')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_skips_existing_files_without_force(): void
    {
        config(['type-bridge.output_format' => 'ts']);

        // Publish once
        $this->artisan('type-bridge:publish-translator-utils')
            ->assertExitCode(0);

        // Try to publish again without --force
        $this->artisan('type-bridge:publish-translator-utils')
            ->expectsOutputToContain('already exists')
            ->expectsOutputToContain('Use --force to overwrite')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_overwrites_existing_files_with_force(): void
    {
        config(['type-bridge.output_format' => 'ts']);

        // Publish once
        $this->artisan('type-bridge:publish-translator-utils')
            ->assertExitCode(0);

        // Publish again with --force
        $this->artisan('type-bridge:publish-translator-utils', ['--force' => true])
            ->expectsOutputToContain('Published:')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_reports_error_when_stub_file_not_found(): void
    {
        config(['type-bridge.output_format' => 'ts']);

        // Temporarily rename stub directory to simulate missing stubs
        $stubDir = __DIR__.'/../../stubs';
        $backupDir = __DIR__.'/../../stubs_backup';

        if (File::exists($stubDir)) {
            File::moveDirectory($stubDir, $backupDir);
        }

        try {
            $this->artisan('type-bridge:publish-translator-utils')
                ->expectsOutputToContain('Stub file not found')
                ->assertExitCode(0);
        } finally {
            // Restore stubs
            if (File::exists($backupDir)) {
                File::moveDirectory($backupDir, $stubDir);
            }
        }
    }
}
