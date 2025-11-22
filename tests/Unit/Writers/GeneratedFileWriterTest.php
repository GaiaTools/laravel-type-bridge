<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\Writers;

use GaiaTools\TypeBridge\Tests\TestCase;
use GaiaTools\TypeBridge\ValueObjects\GeneratedFile;
use GaiaTools\TypeBridge\Writers\GeneratedFileWriter;
use Illuminate\Support\Facades\File;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;

class GeneratedFileWriterTest extends TestCase
{
    private GeneratedFileWriter $writer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->writer = new GeneratedFileWriter();
    }

    #[Test]
    public function it_creates_directory_when_missing_and_writes_file(): void
    {
        $path = resource_path('test-output/tmp/hello.ts');
        $dir = dirname($path);
        if (File::exists($dir)) {
            File::deleteDirectory($dir);
        }
        $file = new GeneratedFile($path, 'content');

        $this->writer->write($file);

        $this->assertTrue(File::exists($dir));
        $this->assertTrue(File::exists($path));
        $this->assertSame('content', File::get($path));
    }

    #[Test]
    public function it_writes_file_when_directory_already_exists(): void
    {
        $path = resource_path('test-output/tmp/existing.ts');
        $dir = dirname($path);
        if (! File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
        $file = new GeneratedFile($path, 'ok');

        $this->writer->write($file);

        $this->assertTrue(File::exists($path));
        $this->assertSame('ok', File::get($path));
    }

    #[Test]
    public function it_bubbles_up_errors_from_put_operation(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('disk full');

        $path = resource_path('test-output/tmp/error.ts');
        $file = new GeneratedFile($path, 'x');

        // Ensure directory exists using real FS
        $dir = dirname($path);
        if (! File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        // Partially mock only the put call; other File methods should pass through
        File::partialMock()->shouldReceive('put')->once()
            ->with($path, 'x')
            ->andThrow(new \RuntimeException('disk full'));

        $this->writer->write($file);
    }
}
