<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Feature;

use GaiaTools\TypeBridge\Adapters\I18nextSyntaxAdapter;
use GaiaTools\TypeBridge\Discoverers\SimpleDiscoverer;
use GaiaTools\TypeBridge\Generators\TranslationGenerator;
use GaiaTools\TypeBridge\OutputFormatters\Translation\JsonTranslationFormatter;
use GaiaTools\TypeBridge\OutputFormatters\Translation\JsTranslationFormatter;
use GaiaTools\TypeBridge\OutputFormatters\Translation\TsTranslationFormatter;
use GaiaTools\TypeBridge\Tests\TestCase;
use GaiaTools\TypeBridge\Transformers\TranslationTransformer;
use GaiaTools\TypeBridge\Writers\GeneratedFileWriter;
use PHPUnit\Framework\Attributes\Test;

class TranslationGeneratorTest extends TestCase
{
    #[Test]
    public function it_generates_typescript_translation_files(): void
    {
        $generatorConfig = self::createGeneratorConfig();
        $adapter = new I18nextSyntaxAdapter;

        $discoverer = new SimpleDiscoverer(['locale' => 'en', 'flat' => false]);
        $transformer = new TranslationTransformer($generatorConfig, $adapter);
        $formatter = new TsTranslationFormatter;
        $writer = new GeneratedFileWriter;

        $generator = new TranslationGenerator($discoverer, $transformer, $formatter, $writer);

        $files = $generator->generate();

        $this->assertCount(1, $files);

        $file = $files->first();
        $this->assertFileExists($file->path);
        $this->assertStringContainsString('// !!!!', $file->contents);
        $this->assertStringContainsString('export const en =', $file->contents);
        $this->assertStringContainsString('as const', $file->contents);
        $this->assertStringContainsString('export type en = typeof en', $file->contents);
    }

    #[Test]
    public function it_generates_javascript_translation_files(): void
    {
        $generatorConfig = self::createGeneratorConfig(
            outputFormat: 'js',
        );
        $adapter = new I18nextSyntaxAdapter;

        $discoverer = new SimpleDiscoverer(['locale' => 'en', 'flat' => false]);
        $transformer = new TranslationTransformer($generatorConfig, $adapter);
        $formatter = new JsTranslationFormatter;
        $writer = new GeneratedFileWriter;

        $generator = new TranslationGenerator($discoverer, $transformer, $formatter, $writer);

        $files = $generator->generate();

        $this->assertCount(1, $files);

        $file = $files->first();
        $this->assertFileExists($file->path);
        $this->assertStringContainsString('export const en =', $file->contents);
        $this->assertStringNotContainsString('as const', $file->contents);
    }

    #[Test]
    public function it_generates_json_translation_files(): void
    {
        $generatorConfig = self::createGeneratorConfig(
            outputFormat: 'json',
        );
        $adapter = new I18nextSyntaxAdapter;

        $discoverer = new SimpleDiscoverer(['locale' => 'en', 'flat' => false]);
        $transformer = new TranslationTransformer($generatorConfig, $adapter);
        $formatter = new JsonTranslationFormatter;
        $writer = new GeneratedFileWriter;

        $generator = new TranslationGenerator($discoverer, $transformer, $formatter, $writer);

        $files = $generator->generate();

        $this->assertCount(1, $files);

        $file = $files->first();
        $this->assertFileExists($file->path);

        $decoded = json_decode($file->contents, true);
        $this->assertIsArray($decoded);
    }

    #[Test]
    public function it_hoists_enum_translations_to_root_level(): void
    {
        $generatorConfig = self::createGeneratorConfig();
        $adapter = new I18nextSyntaxAdapter;

        $discoverer = new SimpleDiscoverer(['locale' => 'en', 'flat' => false]);
        $transformer = new TranslationTransformer($generatorConfig, $adapter);
        $formatter = new TsTranslationFormatter;
        $writer = new GeneratedFileWriter;

        $generator = new TranslationGenerator($discoverer, $transformer, $formatter, $writer);

        $files = $generator->generate();
        $file = $files->first();

        // Enum translations should be at root, not nested under 'enums'
        $this->assertStringContainsString('"TestStatus"', $file->contents);
        $this->assertStringContainsString('"TestPriority"', $file->contents);

        // Other translations should be grouped
        $this->assertStringContainsString('"messages"', $file->contents);
        $this->assertStringContainsString('"validation"', $file->contents);
    }

    #[Test]
    public function it_handles_flat_translations(): void
    {
        $generatorConfig = self::createGeneratorConfig();
        $adapter = new I18nextSyntaxAdapter;

        $discoverer = new SimpleDiscoverer(['locale' => 'en', 'flat' => true]);
        $transformer = new TranslationTransformer($generatorConfig, $adapter);
        $formatter = new TsTranslationFormatter;
        $writer = new GeneratedFileWriter;

        $generator = new TranslationGenerator($discoverer, $transformer, $formatter, $writer);

        $files = $generator->generate();
        $file = $files->first();

        // Should have dot-notated keys
        $this->assertStringContainsString('"messages.welcome"', $file->contents);
        $this->assertStringContainsString('"validation.required"', $file->contents);
    }
}
