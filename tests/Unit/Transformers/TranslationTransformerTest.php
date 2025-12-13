<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\Transformers;

use GaiaTools\TypeBridge\Adapters\I18nextSyntaxAdapter;
use GaiaTools\TypeBridge\Config\TranslationDiscoveryConfig;
use GaiaTools\TypeBridge\Tests\TestCase;
use GaiaTools\TypeBridge\Transformers\TranslationTransformer;
use GaiaTools\TypeBridge\ValueObjects\TransformedTranslation;
use Illuminate\Support\Facades\File;
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
    public function it_hoists_nested_enums_key_inside_grouped_file(): void
    {
        $source = ['locale' => 'en', 'flat' => false];

        $result = $this->transformer->transform($source);

        // The 'mixed.php' file contains a nested 'enums' key; it should be hoisted and 'enums' removed
        $this->assertArrayHasKey('mixed', $result->data);
        $this->assertIsArray($result->data['mixed']);
        $this->assertArrayNotHasKey('enums', $result->data['mixed']);
        $this->assertArrayHasKey('NestedStatus', $result->data['mixed']);
        $this->assertArrayHasKey('A', $result->data['mixed']['NestedStatus']);
        $this->assertSame('a', $result->data['mixed']['NestedStatus']['A']);
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

    #[Test]
    public function it_ignores_php_files_that_do_not_return_arrays(): void
    {
        // The fixture non_array.php returns a string, so it should be ignored
        $source = ['locale' => 'en', 'flat' => false];

        $result = $this->transformer->transform($source);

        $this->assertArrayNotHasKey('non_array', $result->data);
    }

    #[Test]
    public function it_flattens_objects_with_and_without_to_string(): void
    {
        $source = ['locale' => 'en', 'flat' => true];

        $result = $this->transformer->transform($source);

        // From fixtures/lang/en/objects.php
        $this->assertArrayHasKey('objects.with_to_string', $result->data);
        $this->assertSame('stringable', $result->data['objects.with_to_string']);

        // Non-stringable object should become null when flattened
        $this->assertArrayHasKey('objects.plain_object', $result->data);
        $this->assertNull($result->data['objects.plain_object']);
    }

    #[Test]
    public function it_uses_provided_discovery_config_instead_of_default(): void
    {
        // Create a temporary custom lang root that is not part of default fixtures
        $tempRoot = base_path('tmp_custom_lang');
        $enDir = $tempRoot.'/en';
        File::deleteDirectory($tempRoot);
        File::makeDirectory($enDir, 0755, true);

        // Create a single file in this custom root
        file_put_contents($enDir.'/custom.php', "<?php\nreturn ['key' => 'value'];\n");

        $config = self::createGeneratorConfig();
        $syntaxAdapter = new I18nextSyntaxAdapter;
        $disc = new TranslationDiscoveryConfig([$tempRoot]);
        $transformer = new TranslationTransformer($config, $syntaxAdapter, $disc);

        $result = $transformer->transform(['locale' => 'en', 'flat' => false]);

        // Should only have 'custom' group from temp root, not the default fixtures like 'messages'
        $this->assertArrayHasKey('custom', $result->data);
        $this->assertSame(['key' => 'value'], $result->data['custom']);
        $this->assertArrayNotHasKey('messages', $result->data);

        // Cleanup
        File::deleteDirectory($tempRoot);
    }
}
