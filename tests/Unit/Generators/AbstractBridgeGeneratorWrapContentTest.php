<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\Generators;

use GaiaTools\TypeBridge\Contracts\Discoverer;
use GaiaTools\TypeBridge\Contracts\OutputFormatter;
use GaiaTools\TypeBridge\Contracts\Transformer;
use GaiaTools\TypeBridge\Generators\AbstractBridgeGenerator;
use GaiaTools\TypeBridge\Tests\TestCase;
use GaiaTools\TypeBridge\ValueObjects\GeneratedFile;
use GaiaTools\TypeBridge\Writers\GeneratedFileWriter;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;

class AbstractBridgeGeneratorWrapContentTest extends TestCase
{
    /**
     * Minimal concrete generator to expose wrapContent for testing.
     */
    private function makeGeneratorFor(OutputFormatter $formatter): object
    {
        $discoverer = new class implements Discoverer {
            public function discover(): Collection { return collect(); }
        };
        $transformer = new class implements Transformer {
            public function transform(mixed $item): mixed { return $item; }
        };
        $writer = new GeneratedFileWriter(); // not used during tests

        return new class($discoverer, $transformer, $formatter, $writer) extends AbstractBridgeGenerator {
            public function __construct($d, $t, $f, $w) { parent::__construct($d, $t, $f, $w); }
            protected function buildFilePath(mixed $transformed): string { return 'ignored'; }
            public function getName(): string { return 'test-generator'; }
            public function exposeWrap(string $content): string { return $this->wrapContent($content); }
        };
    }

    #[Test]
    public function it_injects_generated_header_for_non_json_and_not_for_json(): void
    {
        $tsFormatter = new class implements OutputFormatter {
            public function format(mixed $transformed): string { return ''; }
            public function getExtension(): string { return 'ts'; }
        };
        $jsonFormatter = new class implements OutputFormatter {
            public function format(mixed $transformed): string { return ''; }
            public function getExtension(): string { return 'json'; }
        };

        $genTs = $this->makeGeneratorFor($tsFormatter);
        $genJson = $this->makeGeneratorFor($jsonFormatter);

        // Non-JSON path should prepend warnings + blank line
        $tsWrapped = $genTs->exposeWrap("line1\nline2\n");
        $this->assertStringStartsWith(GeneratedFile::GENERATED_FILE_WARNING[0], $tsWrapped);
        $this->assertStringContainsString("\n\nline1\nline2\n", $tsWrapped); // blank line before content

        // JSON path should not include header
        $jsonWrapped = $genJson->exposeWrap("{\n  \"a\": 1\n}\n");
        $this->assertStringNotContainsString(GeneratedFile::GENERATED_FILE_WARNING[0], $jsonWrapped);
        // JSON still gets a leading blank line (header omitted only)
        $this->assertSame("\n{\n  \"a\": 1\n}\n", $jsonWrapped);
    }

    #[Test]
    public function it_normalizes_newlines_and_does_not_add_extra_trailing_blank_lines(): void
    {
        $tsFormatter = new class implements OutputFormatter {
            public function format(mixed $transformed): string { return ''; }
            public function getExtension(): string { return 'ts'; }
        };
        $proxy = $this->makeGeneratorFor($tsFormatter);

        $mixedNewlines = "L1\r\nL2\rL3\n\n"; // ends with one extra blank line
        $result = $proxy->exposeWrap($mixedNewlines);

        // After normalization and header + blank line, we expect: header, blank, L1, L2, L3 and a single trailing \n
        $lines = array_merge(GeneratedFile::GENERATED_FILE_WARNING, ['','L1','L2','L3']);
        $expected = implode("\n", $lines)."\n";
        $this->assertSame($expected, $result);
    }

    #[Test]
    public function it_honors_max_line_length_and_disables_when_config_is_non_positive(): void
    {
        $tsFormatter = new class implements OutputFormatter {
            public function format(mixed $transformed): string { return ''; }
            public function getExtension(): string { return 'ts'; }
        };
        $proxy = $this->makeGeneratorFor($tsFormatter);

        // Force very small max length to trigger eslint disable
        config()->set('type-bridge.max_line_length', 10);
        $longLine = str_repeat('a', 20);
        $wrapped = $proxy->exposeWrap($longLine."\n");
        $this->assertStringStartsWith('/* eslint-disable max-len */', $wrapped);

        // Disable via <= 0 should NOT add eslint directive
        config()->set('type-bridge.max_line_length', 0);
        $wrappedDisabled = $proxy->exposeWrap($longLine."\n");
        $this->assertStringNotContainsString('/* eslint-disable max-len */', $wrappedDisabled);
    }
}
