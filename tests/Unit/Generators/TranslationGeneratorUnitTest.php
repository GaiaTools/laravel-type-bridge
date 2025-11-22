<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\Generators;

use GaiaTools\TypeBridge\Contracts\Discoverer;
use GaiaTools\TypeBridge\Contracts\OutputFormatter;
use GaiaTools\TypeBridge\Contracts\Transformer;
use GaiaTools\TypeBridge\Generators\TranslationGenerator;
use GaiaTools\TypeBridge\Tests\TestCase;
use GaiaTools\TypeBridge\ValueObjects\TransformedTranslation;
use GaiaTools\TypeBridge\Writers\GeneratedFileWriter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;

class TranslationGeneratorUnitTest extends TestCase
{
    #[Test]
    public function get_name_returns_translations(): void
    {
        $discoverer = new class implements Discoverer
        {
            public function discover(): Collection
            {
                return collect();
            }
        };

        $transformer = new class implements Transformer
        {
            public function transform(mixed $source): mixed
            {
                return $source;
            }
        };

        $formatter = new class implements OutputFormatter
        {
            public function format(mixed $transformed): string
            {
                return '';
            }

            public function getExtension(): string
            {
                return 'ts';
            }
        };

        $writer = new GeneratedFileWriter;

        $generator = new TranslationGenerator($discoverer, $transformer, $formatter, $writer);

        $this->assertSame('translations', $generator->getName());
    }

    #[Test]
    public function build_file_path_composes_with_formatter_extension(): void
    {
        $outputPath = resource_path('test-output/translations');

        $t = new TransformedTranslation(
            locale: 'en',
            data: [],
            isFlat: true,
            outputPath: $outputPath,
        );

        $discoverer = new class implements Discoverer
        {
            public function discover(): Collection
            {
                return collect();
            }
        };

        $transformer = new class implements Transformer
        {
            public function transform(mixed $source): mixed
            {
                return $source;
            }
        };

        // Case A: ts
        $formatterTs = new class implements OutputFormatter
        {
            public function format(mixed $transformed): string
            {
                return '';
            }

            public function getExtension(): string
            {
                return 'ts';
            }
        };

        $generatorTs = new TranslationGenerator($discoverer, $transformer, $formatterTs, new GeneratedFileWriter);

        $ref = new \ReflectionClass($generatorTs);
        $method = $ref->getMethod('buildFilePath');
        $method->setAccessible(true);

        $pathTs = $method->invoke($generatorTs, $t);
        $this->assertSame($outputPath.'/en.ts', $pathTs);

        // Case B: json
        $formatterJson = new class implements OutputFormatter
        {
            public function format(mixed $transformed): string
            {
                return '';
            }

            public function getExtension(): string
            {
                return 'json';
            }
        };

        $generatorJson = new TranslationGenerator($discoverer, $transformer, $formatterJson, new GeneratedFileWriter);
        $pathJson = $method->invoke($generatorJson, $t);
        $this->assertSame($outputPath.'/en.json', $pathJson);
    }

    #[Test]
    public function generate_uses_built_file_path_and_writes_files_with_header_for_non_json(): void
    {
        $outputPath = resource_path('test-output/translations');

        // Discoverer returns two locales
        $discoverer = new class implements Discoverer
        {
            public function discover(): Collection
            {
                return collect(['en', 'fr']);
            }
        };

        // Transformer maps locale -> TransformedTranslation
        $transformer = new class($outputPath) implements Transformer
        {
            public function __construct(private string $path) {}

            public function transform(mixed $source): mixed
            {
                return new TransformedTranslation(
                    locale: (string) $source,
                    data: ['k' => 'v'],
                    isFlat: true,
                    outputPath: $this->path,
                );
            }
        };

        // Formatter: extension ts, content deterministic
        $formatter = new class implements OutputFormatter
        {
            public function format(mixed $transformed): string
            {
                \assert($transformed instanceof TransformedTranslation);

                return 'CONTENT-'.$transformed->locale;
            }

            public function getExtension(): string
            {
                return 'ts';
            }
        };

        // Mock filesystem writes to avoid touching real FS and capture calls
        $captures = [];
        // TestCase setUp/tearDown uses File::exists/deleteDirectory; stub them to avoid unexpected calls
        File::shouldReceive('exists')->andReturn(false);
        File::shouldReceive('deleteDirectory')->andReturnTrue();
        File::shouldReceive('isDirectory')->andReturn(true);
        File::shouldReceive('put')
            ->twice()
            ->withArgs(function (string $path, string $contents) use (&$captures) {
                $captures[] = compact('path', 'contents');

                return true;
            })
            ->andReturnTrue();

        $writer = new GeneratedFileWriter;

        $generator = new TranslationGenerator($discoverer, $transformer, $formatter, $writer);
        $files = $generator->generate();

        // Returned collection should contain two GeneratedFile instances
        $this->assertCount(2, $files);

        // Paths should use buildFilePath() result
        $this->assertSame($outputPath.'/en.ts', $files->get(0)->path);
        $this->assertSame($outputPath.'/fr.ts', $files->get(1)->path);

        // Contents should include header (non-JSON) and our content after a blank line
        $this->assertStringContainsString('// !!!!', $files->get(0)->contents);
        $this->assertStringContainsString('// This is a generated file.', $files->get(0)->contents);
        $this->assertStringContainsString('CONTENT-en', $files->get(0)->contents);
        $this->assertStringContainsString('CONTENT-fr', $files->get(1)->contents);

        // Also confirm filesystem put calls were captured with expected paths
        $this->assertSame($outputPath.'/en.ts', $captures[0]['path']);
        $this->assertSame($outputPath.'/fr.ts', $captures[1]['path']);
    }

    #[Test]
    public function generate_json_does_not_prepend_header(): void
    {
        $outputPath = resource_path('test-output/translations');

        $discoverer = new class implements Discoverer
        {
            public function discover(): Collection
            {
                return collect(['en']);
            }
        };

        $transformer = new class($outputPath) implements Transformer
        {
            public function __construct(private string $path) {}

            public function transform(mixed $source): mixed
            {
                return new TransformedTranslation(
                    locale: (string) $source,
                    data: ['a' => 'b'],
                    isFlat: true,
                    outputPath: $this->path,
                );
            }
        };

        $formatter = new class implements OutputFormatter
        {
            public function format(mixed $transformed): string
            {
                \assert($transformed instanceof TransformedTranslation);

                return 'JSON-'.$transformed->locale;
            }

            public function getExtension(): string
            {
                return 'json';
            }
        };

        // Expect a single write; no header lines should be present
        $captures = [];
        // Stub TestCase cleanup calls
        File::shouldReceive('exists')->andReturn(false);
        File::shouldReceive('deleteDirectory')->andReturnTrue();
        File::shouldReceive('isDirectory')->andReturn(true);
        File::shouldReceive('put')
            ->once()
            ->withArgs(function (string $path, string $contents) use (&$captures) {
                $captures[] = compact('path', 'contents');

                return true;
            })
            ->andReturnTrue();

        $writer = new GeneratedFileWriter;

        $generator = new TranslationGenerator($discoverer, $transformer, $formatter, $writer);
        $files = $generator->generate();

        $this->assertCount(1, $files);
        $this->assertSame($outputPath.'/en.json', $files->get(0)->path);

        // No generated file header for JSON
        $this->assertStringNotContainsString('// This is a generated file.', $files->get(0)->contents);
        $this->assertStringNotContainsString('// !!!!', $files->get(0)->contents);
        $this->assertStringContainsString('JSON-en', $files->get(0)->contents);
    }

    #[Test]
    public function generate_with_empty_discovery_returns_empty_and_never_writes(): void
    {
        $discoverer = new class implements Discoverer
        {
            public function discover(): Collection
            {
                return collect();
            }
        };

        $transformer = new class implements Transformer
        {
            public function transform(mixed $source): mixed
            {
                return $source;
            }
        };

        $formatter = new class implements OutputFormatter
        {
            public function format(mixed $transformed): string
            {
                return '';
            }

            public function getExtension(): string
            {
                return 'ts';
            }
        };

        // Expect no filesystem writes; also stub TestCase cleanup calls
        File::shouldReceive('exists')->andReturn(false);
        File::shouldReceive('deleteDirectory')->andReturnTrue();
        File::shouldReceive('isDirectory')->never();
        File::shouldReceive('put')->never();

        $writer = new GeneratedFileWriter;

        $generator = new TranslationGenerator($discoverer, $transformer, $formatter, $writer);
        $files = $generator->generate();

        $this->assertCount(0, $files);
    }
}
