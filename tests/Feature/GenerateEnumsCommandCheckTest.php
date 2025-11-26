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

final class GenerateEnumsCommandCheckTest extends TestCase
{
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

        // Tamper with one generated file to remove a case so that backend has an added case
        $path = resource_path('test-output/enums/TestStatus.ts');
        $this->assertFileExists($path, 'Expected previously generated TestStatus.ts to exist');
        $contents = File::get($path);
        // Remove the INACTIVE line if present
        $modified = preg_replace('/\n\s*INACTIVE:\s*[^,]+,\n/m', "\n", $contents);
        if ($modified === null) {
            $modified = $contents; // If regex fails, keep contents (test will still validate general failure output)
        }
        File::put($path, $modified);

        $this->artisan('type-bridge:enums', ['--check' => true, '--format' => 'ts'])
            ->expectsOutputToContain('❌ Enums differ from generated frontend files:')
            ->expectsOutputToContain('TestStatus ('.$path.')')
            ->expectsOutputToContain('  + INACTIVE')
            ->assertExitCode(1);
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
}
