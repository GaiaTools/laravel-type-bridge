<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\OutputFormatters;

use GaiaTools\TypeBridge\OutputFormatters\Translation\TsTranslationFormatter;
use GaiaTools\TypeBridge\Tests\TestCase;
use GaiaTools\TypeBridge\ValueObjects\TransformedTranslation;
use PHPUnit\Framework\Attributes\Test;

class TsTranslationFormatterQuotingTest extends TestCase
{
    private TsTranslationFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new TsTranslationFormatter;
    }

    #[Test]
    public function it_formats_translation_as_typescript_const(): void
    {
        $transformed = new TransformedTranslation(
            locale: 'en',
            data: [
                'messages' => [
                    'hello' => 'world',
                ],
            ],
            isFlat: false,
            outputPath: resource_path('test-output/i18n'),
        );

        $result = $this->formatter->format($transformed);

        $this->assertStringContainsString('export const en = {', $result);
        $this->assertStringContainsString('"messages": {', $result);
        $this->assertStringContainsString("\"hello\": 'world'", $result);
        $this->assertStringContainsString('} as const;', $result);
        $this->assertStringContainsString('export type en = typeof en;', $result);
    }

    #[Test]
    public function it_uses_minimal_escapes_for_string_values(): void
    {
        $transformed = new TransformedTranslation(
            locale: 'en',
            data: [
                't' => [
                    'APOSTROPHE' => "don't",
                    'DOUBLE' => 'she said "hi"',
                    'BOTH' => 'He said: "don\'t"',
                    'PATH' => 'C:\\Temp',
                ],
            ],
            isFlat: false,
            outputPath: resource_path('test-output/i18n'),
        );

        $result = $this->formatter->format($transformed);

        // Apostrophe only -> use double quotes, no escaping needed for apostrophe
        $this->assertStringContainsString("\"APOSTROPHE\": \"don't\"", $result);
        // Double quotes only -> use single quotes, leave double quotes inside value
        $this->assertStringContainsString("\"DOUBLE\": 'she said \"hi\"'", $result);
        // Both -> use single quotes and escape apostrophe only
        $this->assertStringContainsString("\"BOTH\": 'He said: \"don\\'t\"'", $result);
        // Backslashes must be doubled in JS/TS string (two backslashes in output)
        $this->assertStringContainsString("\"PATH\": 'C:\\\\Temp'", $result);
        $this->assertStringContainsString('} as const;', $result);
    }
}
