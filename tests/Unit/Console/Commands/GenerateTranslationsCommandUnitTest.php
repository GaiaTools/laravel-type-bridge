<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\Console\Commands;

use GaiaTools\TypeBridge\Config\TranslationDiscoveryConfig;
use GaiaTools\TypeBridge\Console\Commands\GenerateTranslationsCommand;
use GaiaTools\TypeBridge\Tests\TestCase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;

final class GenerateTranslationsCommandUnitTest extends TestCase
{
    #[Test]
    public function it_executes_continue_branch_when_non_directory_root_is_encountered(): void
    {
        // Ensure there is a valid locale directory alongside a non-existent one
        $validLangRoot = $this->app->langPath();
        File::ensureDirectoryExists($validLangRoot.'/en');
        File::put($validLangRoot.'/en/messages.php', "<?php\nreturn ['hello' => 'Hello'];");

        $nonExistent = resource_path('non-existent-lang-root');
        if (File::exists($nonExistent)) {
            File::deleteDirectory($nonExistent);
        }

        $config = new TranslationDiscoveryConfig(
            langPaths: [
                $nonExistent,      // should hit the `continue` branch in discoverLocales
                $validLangRoot,    // valid path to still discover 'en'
            ],
        );

        $command = new GenerateTranslationsCommand();

        // Use reflection to directly invoke the private discoverLocales method
        $method = new \ReflectionMethod($command, 'discoverLocales');
        $method->setAccessible(true);
        /** @var array $locales */
        $locales = $method->invoke($command, $config);

        // The method should have skipped the missing root and still discover 'en'
        $this->assertContains('en', $locales);
    }
}
