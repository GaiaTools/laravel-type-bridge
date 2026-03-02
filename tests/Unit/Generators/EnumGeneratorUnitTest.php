<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\Generators;

use GaiaTools\TypeBridge\Contracts\Discoverer;
use GaiaTools\TypeBridge\Contracts\OutputFormatter;
use GaiaTools\TypeBridge\Contracts\Transformer;
use GaiaTools\TypeBridge\Generators\EnumGenerator;
use GaiaTools\TypeBridge\Tests\TestCase;
use GaiaTools\TypeBridge\ValueObjects\GeneratedFile;
use GaiaTools\TypeBridge\ValueObjects\TransformedEnum;
use GaiaTools\TypeBridge\Writers\GeneratedFileWriter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;

class EnumGeneratorUnitTest extends TestCase
{
    #[Test]
    public function get_name_returns_enums(): void
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

        $generator = new EnumGenerator($discoverer, $transformer, $formatter, $writer);

        $this->assertSame('enums', $generator->getName());
    }

    #[Test]
    public function build_file_path_composes_with_formatter_extension(): void
    {
        $outputPath = resource_path('test-output/enums');

        $t = new TransformedEnum(
            name: 'OrderStatus',
            cases: collect(),
            namespace: 'App\\Enums',
            outputPath: $outputPath,

            groups: collect(),
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

        $generatorTs = new EnumGenerator($discoverer, $transformer, $formatterTs, new GeneratedFileWriter);

        $ref = new \ReflectionClass($generatorTs);
        $method = $ref->getMethod('buildFilePath');
        $method->setAccessible(true);

        $pathTs = $method->invoke($generatorTs, $t);
        $this->assertSame($outputPath.'/OrderStatus.ts', $pathTs);

        // Case B: js
        $formatterJs = new class implements OutputFormatter
        {
            public function format(mixed $transformed): string
            {
                return '';
            }

            public function getExtension(): string
            {
                return 'js';
            }
        };

        $generatorJs = new EnumGenerator($discoverer, $transformer, $formatterJs, new GeneratedFileWriter);
        $pathJs = $method->invoke($generatorJs, $t);
        $this->assertSame($outputPath.'/OrderStatus.js', $pathJs);
    }

    #[Test]
    public function generate_uses_built_file_path_and_writes_files(): void
    {
        $outputPath = resource_path('test-output/enums');

        // Discoverer returns two items
        $discoverer = new class implements Discoverer
        {
            public function discover(): Collection
            {
                return collect([1, 2]);
            }
        };

        // Transformer maps item -> TransformedEnum with derived name
        $transformer = new class($outputPath) implements Transformer
        {
            public function __construct(private string $path) {}

            public function transform(mixed $source): mixed
            {
                return new TransformedEnum(
                    name: 'Enum'.$source,
                    cases: collect(),
                    namespace: 'App',
                    outputPath: $this->path,

                    groups: collect(),
                );
            }
        };

        // Formatter: extension ts, content deterministic
        $formatter = new class implements OutputFormatter
        {
            public function format(mixed $transformed): string
            {
                \assert($transformed instanceof TransformedEnum);

                return 'CONTENT-'.$transformed->name;
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

        $generator = new EnumGenerator($discoverer, $transformer, $formatter, $writer);
        $files = $generator->generate();

        // Returned collection should contain two GeneratedFile instances
        $this->assertCount(2, $files);

        // Paths should use buildFilePath() result
        $this->assertSame($outputPath.'/Enum1.ts', $files->get(0)->path);
        $this->assertSame($outputPath.'/Enum2.ts', $files->get(1)->path);

        // Contents should include header (non-JSON) and our content after a blank line
        $this->assertStringContainsString('// !!!!', $files->get(0)->contents);
        $this->assertStringContainsString('// This is a generated file.', $files->get(0)->contents);
        $this->assertStringContainsString('CONTENT-Enum1', $files->get(0)->contents);
        $this->assertStringContainsString('CONTENT-Enum2', $files->get(1)->contents);

        // Also confirm filesystem put calls were captured with expected paths
        $this->assertSame($outputPath.'/Enum1.ts', $captures[0]['path']);
        $this->assertSame($outputPath.'/Enum2.ts', $captures[1]['path']);
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

        $generator = new EnumGenerator($discoverer, $transformer, $formatter, $writer);
        $files = $generator->generate();

        $this->assertCount(0, $files);
    }
}
