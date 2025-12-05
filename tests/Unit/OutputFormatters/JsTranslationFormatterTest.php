<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\OutputFormatters;

use GaiaTools\TypeBridge\OutputFormatters\Translation\JsTranslationFormatter;
use GaiaTools\TypeBridge\Tests\TestCase;
use GaiaTools\TypeBridge\ValueObjects\TransformedTranslation;
use PHPUnit\Framework\Attributes\Test;

class JsTranslationFormatterTest extends TestCase
{
    private JsTranslationFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new JsTranslationFormatter;
    }

    #[Test]
    public function it_formats_translation_as_javascript_const(): void
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
        $this->assertStringContainsString('};', $result);
        $this->assertStringNotContainsString('as const', $result);
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
        // Backslashes must be doubled in JS string (two backslashes in output)
        $this->assertStringContainsString("\"PATH\": 'C:\\\\Temp'", $result);
    }

    #[Test]
    public function it_includes_trailing_commas_when_config_is_true(): void
    {
        config(['type-bridge.trailing_commas' => true]);

        $transformed = new TransformedTranslation(
            locale: 'en',
            data: [
                'messages' => [
                    'hello' => 'world',
                    'goodbye' => 'farewell',
                ],
            ],
            isFlat: false,
            outputPath: resource_path('test-output/i18n'),
        );

        $result = $this->formatter->format($transformed);

        $this->assertStringContainsString("\"hello\": 'world',", $result);
        $this->assertStringContainsString("\"goodbye\": 'farewell',", $result);
    }

    #[Test]
    public function it_excludes_trailing_commas_when_config_is_false(): void
    {
        config(['type-bridge.trailing_commas' => false]);

        $transformed = new TransformedTranslation(
            locale: 'en',
            data: [
                'messages' => [
                    'hello' => 'world',
                    'goodbye' => 'farewell',
                ],
            ],
            isFlat: false,
            outputPath: resource_path('test-output/i18n'),
        );

        $result = $this->formatter->format($transformed);

        $this->assertStringContainsString("\"hello\": 'world',", $result);
        $this->assertStringContainsString("\"goodbye\": 'farewell'", $result);
        $this->assertStringNotContainsString("\"goodbye\": 'farewell',", $result);
    }
}
