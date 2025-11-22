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

class EnumGeneratorTest extends TestCase
{
    #[Test]
    public function it_generates_typescript_enum_files(): void
    {
        $discoveryConfig = new EnumDiscoveryConfig(
            paths: [__DIR__.'/../Fixtures/Enums'],
            generateBackedEnums: true,
            excludes: ['TestNoComments'],
        );

        $generatorConfig = self::createGeneratorConfig();

        $discoverer = new EnumDiscoverer($discoveryConfig);
        $transformer = new EnumTransformer($generatorConfig);
        $formatter = new TsEnumFormatter;
        $writer = new GeneratedFileWriter;

        $generator = new EnumGenerator($discoverer, $transformer, $formatter, $writer);

        $files = $generator->generate();

        $this->assertGreaterThan(0, $files->count());

        foreach ($files as $file) {
            $this->assertFileExists($file->path);
            $this->assertStringContainsString('// !!!!', $file->contents);
            $this->assertStringContainsString('// This is a generated file.', $file->contents);
            $this->assertStringContainsString('export const', $file->contents);
            $this->assertStringContainsString('as const', $file->contents);
            $this->assertStringContainsString('export type', $file->contents);
        }
    }

    #[Test]
    public function it_generates_javascript_enum_files(): void
    {
        $discoveryConfig = new EnumDiscoveryConfig(
            paths: [__DIR__.'/../Fixtures/Enums'],
            generateBackedEnums: true,
            excludes: ['TestNoComments'],
        );

        $generatorConfig = static::createGeneratorConfig(
            outputFormat: 'js',
        );

        $discoverer = new EnumDiscoverer($discoveryConfig);
        $transformer = new EnumTransformer($generatorConfig);
        $formatter = new JsEnumFormatter;
        $writer = new GeneratedFileWriter;

        $generator = new EnumGenerator($discoverer, $transformer, $formatter, $writer);

        $files = $generator->generate();

        $this->assertGreaterThan(0, $files->count());

        foreach ($files as $file) {
            $this->assertFileExists($file->path);
            $this->assertStringContainsString('export const', $file->contents);
            $this->assertStringNotContainsString('as const', $file->contents);
            $this->assertStringNotContainsString('export type', $file->contents);
        }
    }

    #[Test]
    public function it_creates_output_directory_if_not_exists(): void
    {
        $outputPath = resource_path('test-output/enums');

        if (File::exists($outputPath)) {
            File::deleteDirectory($outputPath);
        }

        $this->assertDirectoryDoesNotExist($outputPath);

        $discoveryConfig = new EnumDiscoveryConfig(
            paths: [__DIR__.'/../Fixtures/Enums'],
            generateBackedEnums: true,
            excludes: ['TestNoComments'],
        );

        $generatorConfig = self::createGeneratorConfig();

        $discoverer = new EnumDiscoverer($discoveryConfig);
        $transformer = new EnumTransformer($generatorConfig);
        $formatter = new TsEnumFormatter;
        $writer = new GeneratedFileWriter;

        $generator = new EnumGenerator($discoverer, $transformer, $formatter, $writer);
        $generator->generate();

        $this->assertDirectoryExists($outputPath);
    }

    #[Test]
    public function it_respects_exclude_configuration(): void
    {
        $discoveryConfig = new EnumDiscoveryConfig(
            paths: [__DIR__.'/../Fixtures/Enums'],
            generateBackedEnums: true,
            excludes: ['TestStatus', 'TestNoComments'],
        );

        $generatorConfig = self::createGeneratorConfig();

        $discoverer = new EnumDiscoverer($discoveryConfig);
        $transformer = new EnumTransformer($generatorConfig);
        $formatter = new TsEnumFormatter;
        $writer = new GeneratedFileWriter;

        $generator = new EnumGenerator($discoverer, $transformer, $formatter, $writer);
        $files = $generator->generate();

        $filePaths = $files->pluck('path')->map(fn ($path) => basename($path));

        $this->assertNotContains('TestStatus.ts', $filePaths);
    }
}
