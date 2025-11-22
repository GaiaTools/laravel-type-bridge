<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\ValueObjects;

use GaiaTools\TypeBridge\Tests\TestCase;
use GaiaTools\TypeBridge\ValueObjects\GeneratedFile;
use PHPUnit\Framework\Attributes\Test;

class GeneratedFileTest extends TestCase
{
    #[Test]
    public function it_creates_file_from_lines(): void
    {
        $lines = ['line 1', 'line 2', 'line 3'];

        $file = GeneratedFile::fromLines('/tmp/test.ts', $lines);

        $this->assertEquals('/tmp/test.ts', $file->path);
        $this->assertStringContainsString('line 1', $file->contents);
        $this->assertStringContainsString('line 2', $file->contents);
        $this->assertStringContainsString('line 3', $file->contents);
    }

    #[Test]
    public function it_adds_eslint_disable_when_lines_exceed_max_length(): void
    {
        $longLine = str_repeat('x', 150);
        $lines = ['short line', $longLine];

        $file = GeneratedFile::fromLines('/tmp/test.ts', $lines, 120);

        $this->assertStringContainsString('/* eslint-disable max-len */', $file->contents);
    }

    #[Test]
    public function it_does_not_add_eslint_disable_when_within_max_length(): void
    {
        $lines = ['short line', 'another short line'];

        $file = GeneratedFile::fromLines('/tmp/test.ts', $lines, 120);

        $this->assertStringNotContainsString('/* eslint-disable max-len */', $file->contents);
    }

    #[Test]
    public function it_skips_max_length_check_when_disabled(): void
    {
        $longLine = str_repeat('x', 150);
        $lines = ['short line', $longLine];

        $file = GeneratedFile::fromLines('/tmp/test.ts', $lines, null);

        $this->assertStringNotContainsString('/* eslint-disable max-len */', $file->contents);
    }

    #[Test]
    public function it_adds_trailing_newline(): void
    {
        $lines = ['line 1', 'line 2'];

        $file = GeneratedFile::fromLines('/tmp/test.ts', $lines);

        $this->assertStringEndsWith("\n", $file->contents);
    }
}
