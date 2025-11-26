<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Feature;

use GaiaTools\TypeBridge\Config\EnumDiscoveryConfig;
use GaiaTools\TypeBridge\Discoverers\EnumDiscoverer;
use GaiaTools\TypeBridge\Generators\EnumGenerator;
use GaiaTools\TypeBridge\OutputFormatters\Enum\JsEnumFormatter;
use GaiaTools\TypeBridge\OutputFormatters\Enum\TsEnumFormatter;
use GaiaTools\TypeBridge\Tests\TestCase;
use GaiaTools\TypeBridge\Transformers\EnumTransformer;
use GaiaTools\TypeBridge\Writers\GeneratedFileWriter;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;

final class GenerateEnumsCommandTest extends TestCase
{
    private function generateTsEnums(): void
    {
        $discoveryConfig = new EnumDiscoveryConfig(
            paths: [__DIR__.'/../Fixtures/Enums'],
            generateBackedEnums: true,
            excludes: ['TestNoComments'],
        );

        $discoverer = new EnumDiscoverer($discoveryConfig);
        $transformer = new EnumTransformer(self::createGeneratorConfig(outputFormat: 'ts'));
        $formatter = new TsEnumFormatter;
        $writer = new GeneratedFileWriter;

        (new EnumGenerator($discoverer, $transformer, $formatter, $writer))->generate();
    }

    // ---- Generation path tests ----

    #[Test]
    public function it_generates_enums_via_command_default_ts(): void
    {
        $this->artisan('type-bridge:enums')
            ->expectsOutputToContain('Generating enums...')
            ->expectsOutputToContain('Generated')
            ->assertExitCode(0);
        $this->assertDirectoryExists(resource_path('test-output/enums'));
    }

    #[Test]
    public function it_generates_enums_via_command_js_format(): void
    {
        $this->artisan('type-bridge:enums', ['--format' => 'js'])
            ->expectsOutputToContain('Generating enums...')
            ->expectsOutputToContain('Generated')
            ->assertExitCode(0);
        $this->assertDirectoryExists(resource_path('test-output/enums'));
    }

    // ---- --check path: in-sync tests ----

    #[Test]
    public function it_reports_in_sync_for_typescript_enums(): void
    {
        $discoveryConfig = new EnumDiscoveryConfig(
            paths: [__DIR__.'/../Fixtures/Enums'],
            generateBackedEnums: true,
            excludes: ['TestNoComments'],
        );

        $generatorConfig = self::createGeneratorConfig(outputFormat: 'ts');

        $discoverer = new EnumDiscoverer($discoveryConfig);
        $transformer = new EnumTransformer($generatorConfig);
        $formatter = new TsEnumFormatter;
        $writer = new GeneratedFileWriter;

        $generator = new EnumGenerator($discoverer, $transformer, $formatter, $writer);
        $generator->generate();

        $this->artisan('type-bridge:enums', ['--check' => true, '--format' => 'ts'])
            ->expectsOutputToContain('Checking enums against previously generated frontend files...')
            ->expectsOutputToContain('✅ Enums are in sync with generated frontend files.')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_reports_in_sync_for_javascript_enums(): void
    {
        $discoveryConfig = new EnumDiscoveryConfig(
            paths: [__DIR__.'/../Fixtures/Enums'],
            generateBackedEnums: true,
            excludes: ['TestNoComments'],
        );

        $generatorConfig = self::createGeneratorConfig(outputFormat: 'js');

        $discoverer = new EnumDiscoverer($discoveryConfig);
        $transformer = new EnumTransformer($generatorConfig);
        $formatter = new JsEnumFormatter;
        $writer = new GeneratedFileWriter;

        (new EnumGenerator($discoverer, $transformer, $formatter, $writer))->generate();

        $this->artisan('type-bridge:enums', ['--check' => true, '--format' => 'js'])
            ->expectsOutputToContain('✅ Enums are in sync with generated frontend files.')
            ->assertExitCode(0);
    }

    // ---- --check path: drift and reporting tests ----

    #[Test]
    public function it_reports_differences_for_typescript_when_frontend_is_out_of_sync(): void
    {
        $discoveryConfig = new EnumDiscoveryConfig(
            paths: [__DIR__.'/../Fixtures/Enums'],
            generateBackedEnums: true,
            excludes: ['TestNoComments'],
        );

        $generatorConfig = self::createGeneratorConfig(outputFormat: 'ts');

        $discoverer = new EnumDiscoverer($discoveryConfig);
        $transformer = new EnumTransformer($generatorConfig);
        $formatter = new TsEnumFormatter;
        $writer = new GeneratedFileWriter;

        (new EnumGenerator($discoverer, $transformer, $formatter, $writer))->generate();

        $path = resource_path('test-output/enums/TestStatus.ts');
        $this->assertFileExists($path, 'Expected previously generated TestStatus.ts to exist');
        $contents = File::get($path);
        $modified = preg_replace('/\n\s*INACTIVE:\s*[^,]+,\n/m', "\n", $contents);
        if ($modified === null) {
            $modified = $contents;
        }
        File::put($path, $modified);

        $this->artisan('type-bridge:enums', ['--check' => true, '--format' => 'ts'])
            ->expectsOutputToContain('❌ Enums differ from generated frontend files:')
            ->expectsOutputToContain('TestStatus ('.$path.')')
            ->expectsOutputToContain('  + INACTIVE')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_reports_added_when_no_frontend_files_exist_for_ts(): void
    {
        $path = resource_path('test-output/enums/TestStatus.ts');
        $numPath = resource_path('test-output/enums/TestNumeric.ts');

        $this->artisan('type-bridge:enums', ['--check' => true, '--format' => 'ts'])
            ->expectsOutputToContain('❌ Enums differ from generated frontend files:')
            ->expectsOutputToContain('TestStatus ('.$path.')')
            ->expectsOutputToContain("  + ACTIVE: 'active'")
            ->expectsOutputToContain("  + INACTIVE: 'inactive'")
            ->expectsOutputToContain("  + PENDING: 'pending'")
            ->expectsOutputToContain('TestNumeric ('.$numPath.')')
            ->expectsOutputToContain('  + ZERO: 0')
            ->expectsOutputToContain('  + ONE: 1')
            ->expectsOutputToContain('  + TWO: 2')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_reports_value_change_as_add_and_remove(): void
    {
        $this->generateTsEnums();

        $path = resource_path('test-output/enums/TestStatus.ts');
        $this->assertFileExists($path);
        $contents = File::get($path);
        $modified = str_replace("PENDING: 'pending'", "PENDING: 'awaiting'", (string) $contents);
        File::put($path, $modified);

        $this->artisan('type-bridge:enums', ['--check' => true, '--format' => 'ts'])
            ->expectsOutputToContain('❌ Enums differ from generated frontend files:')
            ->expectsOutputToContain('TestStatus ('.$path.')')
            ->expectsOutputToContain("  + PENDING: 'pending'")
            ->expectsOutputToContain("  - PENDING: 'awaiting'")
            ->assertExitCode(1);
    }

    #[Test]
    public function it_ignores_frontend_file_with_mismatched_export_name(): void
    {
        $this->generateTsEnums();

        $path = resource_path('test-output/enums/TestStatus.ts');
        $this->assertFileExists($path);

        $wrong = <<<'TS'
export const SomethingElse = {
    ACTIVE: 'active',
    INACTIVE: 'inactive',
    PENDING: 'pending',
};
TS;
        File::put($path, $wrong);

        $this->artisan('type-bridge:enums', ['--check' => true, '--format' => 'ts'])
            ->expectsOutputToContain('TestStatus ('.$path.')')
            ->expectsOutputToContain("  + ACTIVE: 'active'")
            ->expectsOutputToContain("  + INACTIVE: 'inactive'")
            ->expectsOutputToContain("  + PENDING: 'pending'")
            ->assertExitCode(1);
    }

    #[Test]
    public function it_resolves_js_extension_and_reports_added_with_no_files(): void
    {
        $path = resource_path('test-output/enums/TestStatus.js');

        $this->artisan('type-bridge:enums', ['--check' => true, '--format' => 'js'])
            ->expectsOutputToContain('❌ Enums differ from generated frontend files:')
            ->expectsOutputToContain('TestStatus ('.$path.')')
            ->expectsOutputToContain("  + ACTIVE: 'active'")
            ->assertExitCode(1);
    }

    #[Test]
    public function it_emits_colored_diff_lines_when_decorated_output_is_enabled(): void
    {
        $path = resource_path('test-output/enums/TestStatus.ts');

        $this->artisan('type-bridge:enums', ['--check' => true, '--format' => 'ts', '--ansi' => true])
            ->expectsOutputToContain('TestStatus ('.$path.')')
            ->expectsOutputToContain('+ ACTIVE')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_reports_removed_when_frontend_has_extra_key(): void
    {
        $this->generateTsEnums();

        $path = resource_path('test-output/enums/TestStatus.ts');
        $this->assertFileExists($path);
        $contents = File::get($path);
        $injected = str_replace('export const TestStatus = {', "export const TestStatus = {\n    LEGACY: 'legacy',", (string) $contents);
        File::put($path, $injected);

        $this->artisan('type-bridge:enums', ['--check' => true, '--format' => 'ts'])
            ->expectsOutputToContain('❌ Enums differ from generated frontend files:')
            ->expectsOutputToContain('TestStatus ('.$path.')')
            ->expectsOutputToContain("  - LEGACY: 'legacy'")
            ->assertExitCode(1);
    }

    #[Test]
    public function it_prints_regeneration_hint_without_format_when_not_provided(): void
    {
        config()->set('type-bridge.output_format', '');

        $this->artisan('type-bridge:enums', ['--check' => true])
            ->expectsOutputToContain('Run `php artisan type-bridge:enums` to regenerate.')
            ->assertExitCode(1);
    }
}
