<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\Transformers;

use GaiaTools\TypeBridge\Adapters\I18nextSyntaxAdapter;
use GaiaTools\TypeBridge\Tests\TestCase;
use GaiaTools\TypeBridge\Transformers\TranslationTransformer;
use GaiaTools\TypeBridge\ValueObjects\TransformedTranslation;
use PHPUnit\Framework\Attributes\Test;

class TranslationTransformerTest extends TestCase
{
    private TranslationTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();

        $config = self::createGeneratorConfig();

        $syntaxAdapter = new I18nextSyntaxAdapter;

        $this->transformer = new TranslationTransformer($config, $syntaxAdapter);
    }

    #[Test]
    public function it_transforms_translation_data(): void
    {
        $source = ['locale' => 'en', 'flat' => false];

        $result = $this->transformer->transform($source);

        $this->assertInstanceOf(TransformedTranslation::class, $result);
        $this->assertEquals('en', $result->locale);
        $this->assertFalse($result->isFlat);
        $this->assertIsArray($result->data);
    }

    #[Test]
    public function it_reads_lang_files(): void
    {
        $source = ['locale' => 'en', 'flat' => false];

        $result = $this->transformer->transform($source);

        // Should have hoisted enum translations and other grouped translations
        $this->assertArrayHasKey('TestStatus', $result->data);
        $this->assertArrayHasKey('messages', $result->data);
        $this->assertArrayHasKey('validation', $result->data);
    }

    #[Test]
    public function it_hoists_enum_translations_to_root(): void
    {
        $source = ['locale' => 'en', 'flat' => false];

        $result = $this->transformer->transform($source);

        // Enum translations should be at root level
        $this->assertArrayHasKey('TestStatus', $result->data);
        $this->assertArrayHasKey('TestPriority', $result->data);

        // Should NOT have nested 'enums' key
        $this->assertArrayNotHasKey('enums', $result->data);
    }

    #[Test]
    public function it_keeps_other_translations_grouped(): void
    {
        $source = ['locale' => 'en', 'flat' => false];

        $result = $this->transformer->transform($source);

        // Non-enum translations should be grouped
        $this->assertArrayHasKey('messages', $result->data);
        $this->assertArrayHasKey('validation', $result->data);

        $this->assertEquals('Welcome to our application', $result->data['messages']['welcome']);
    }

    #[Test]
    public function it_flattens_translations_when_requested(): void
    {
        $source = ['locale' => 'en', 'flat' => true];

        $result = $this->transformer->transform($source);

        $this->assertTrue($result->isFlat);

        // Should have dot-notated keys
        $this->assertArrayHasKey('messages.welcome', $result->data);
        $this->assertArrayHasKey('validation.required', $result->data);
    }

    #[Test]
    public function it_normalizes_class_like_keys(): void
    {
        $source = ['locale' => 'en', 'flat' => false];

        $result = $this->transformer->transform($source);

        // Keys should be short names, not FQCNs
        foreach (array_keys($result->data) as $key) {
            $this->assertStringNotContainsString('\\', $key);
        }
    }

    #[Test]
    public function it_throws_exception_for_missing_locale(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Locale directory not found');

        $source = ['locale' => 'nonexistent', 'flat' => false];

        $this->transformer->transform($source);
    }
}
