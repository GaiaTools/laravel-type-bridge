<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\Transformers;

use GaiaTools\TypeBridge\Adapters\I18nextSyntaxAdapter;
use GaiaTools\TypeBridge\Config\TranslationDiscoveryConfig;
use GaiaTools\TypeBridge\Tests\TestCase;
use GaiaTools\TypeBridge\Transformers\TranslationTransformer;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;

class TranslationTransformerConfigDrivenTest extends TestCase
{
    private string $alphaPath;

    private string $betaPath;

    protected function setUp(): void
    {
        parent::setUp();

        // Prepare temporary lang roots
        $this->alphaPath = base_path('tmp_lang/Alpha/lang');
        $this->betaPath = base_path('tmp_lang/Beta/lang');

        // Ensure clean state
        File::deleteDirectory(base_path('tmp_lang'));

        // Create en locales in both roots
        File::makeDirectory($this->alphaPath.'/en', 0755, true);
        File::makeDirectory($this->betaPath.'/en', 0755, true);

        // Alpha provides baseline messages
        File::put($this->alphaPath.'/en/messages.php', <<<'PHP'
<?php
return [
    'welcome' => 'Hello from Alpha',
    'alpha_only' => 'Alpha only',
];
PHP);

        // Beta overrides a key and adds its own
        File::put($this->betaPath.'/en/messages.php', <<<'PHP'
<?php
return [
    'welcome' => 'Hello from Beta',
    'beta_only' => 'Beta only',
];
PHP);
    }

    protected function tearDown(): void
    {
        // Clean temp dirs
        File::deleteDirectory(base_path('tmp_lang'));
        parent::tearDown();
    }

    #[Test]
    public function it_reads_from_configured_paths_and_respects_override_precedence(): void
    {
        // Later paths should override earlier ones
        config(['type-bridge.translations.discovery.include_paths' => [
            $this->alphaPath,
            $this->betaPath,
        ]]);

        $generatorConfig = self::createGeneratorConfig();
        $syntaxAdapter = new I18nextSyntaxAdapter;
        $discovery = TranslationDiscoveryConfig::fromConfig();

        $transformer = new TranslationTransformer($generatorConfig, $syntaxAdapter, $discovery);
        $result = $transformer->transform(['locale' => 'en', 'flat' => false]);

        // messages group exists
        $this->assertArrayHasKey('messages', $result->data);
        $messages = $result->data['messages'];
        $this->assertIsArray($messages);

        // Override honored: Beta value wins
        $this->assertSame('Hello from Beta', $messages['welcome']);

        // Keys from both roots are present
        $this->assertSame('Alpha only', $messages['alpha_only']);
        $this->assertSame('Beta only', $messages['beta_only']);
    }

    #[Test]
    public function it_throws_when_configured_paths_do_not_contain_locale(): void
    {
        // Configure paths that exist but lack the requested locale
        $emptyRoot = base_path('tmp_lang/Empty/lang');
        File::makeDirectory($emptyRoot, 0755, true);
        config(['type-bridge.translations.discovery.include_paths' => [$emptyRoot]]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Locale directory not found for locale');

        $generatorConfig = self::createGeneratorConfig();
        $syntaxAdapter = new I18nextSyntaxAdapter;
        $discovery = TranslationDiscoveryConfig::fromConfig();

        $transformer = new TranslationTransformer($generatorConfig, $syntaxAdapter, $discovery);
        $transformer->transform(['locale' => 'nonexistent', 'flat' => false]);
    }
}
