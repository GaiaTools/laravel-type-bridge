<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests;

use GaiaTools\TypeBridge\Config\GeneratorConfig;
use GaiaTools\TypeBridge\TypeBridgeServiceProvider;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // Copy lang fixtures to the app's lang directory
        $this->setupLangFixtures();

        // Clean up any test output files
        $this->cleanupTestOutputs();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestOutputs();

        parent::tearDown();
    }

    /**
     * Get package providers.
     */
    protected function getPackageProviders($app): array
    {
        return [
            TypeBridgeServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     */
    protected function defineEnvironment($app): void
    {
        // Set up test configuration
        $app['config']->set('type-bridge.output_format', 'ts');
        $app['config']->set('type-bridge.translations_output_format', 'ts');

        // Align test config keys with production config readers
        // Enum discovery
        $app['config']->set('type-bridge.enums.discovery.paths', [
            __DIR__.'/Fixtures/Enums',
        ]);
        // Exclude TestNoComments: this fixture intentionally lacks doc comments to
        // validate comment requirements elsewhere. The --check flow and most
        // generator tests should ignore it to avoid RuntimeException during
        // transformation.
        $app['config']->set('type-bridge.enums.discovery.excludes', ['TestNoComments']);
        $app['config']->set('type-bridge.enums.discovery.generate_backed_enums', true);

        // Output paths used by GeneratorConfig::fromConfig()
        $app['config']->set('type-bridge.enums.output_path', 'test-output/enums');
        $app['config']->set('type-bridge.translations.output_path', 'test-output/translations');
    }

    /**
     * Setup lang fixtures by copying them to Orchestra's lang directory.
     */
    protected function setupLangFixtures(): void
    {
        $fixturesPath = __DIR__.'/Fixtures/lang';
        $langPath = $this->app->langPath();

        if (! File::exists($langPath)) {
            File::makeDirectory($langPath, 0755, true);
        }

        // Copy all fixture lang files to the app's lang directory
        if (File::exists($fixturesPath)) {
            $this->recursiveCopy($fixturesPath, $langPath);
        }
    }

    /**
     * Recursively copy directories.
     */
    protected function recursiveCopy(string $source, string $destination): void
    {
        if (! File::exists($destination)) {
            File::makeDirectory($destination, 0755, true);
        }

        foreach (File::allFiles($source) as $file) {
            $relativePath = str_replace($source, '', $file->getPathname());
            $targetPath = $destination.$relativePath;

            $targetDir = dirname($targetPath);
            if (! File::exists($targetDir)) {
                File::makeDirectory($targetDir, 0755, true);
            }

            File::copy($file->getPathname(), $targetPath);
        }
    }

    protected function cleanupTestOutputs(): void
    {
        $outputPath = resource_path('test-output');

        if (File::exists($outputPath)) {
            File::deleteDirectory($outputPath);
        }
    }

    /**
     * Create a GeneratorConfig with default test values
     */
    public static function createGeneratorConfig(
        string $outputFormat = 'ts',
        string $enumOutputPath = 'test-output/enums',
        string $translationOutputPath = 'test-output/translations',
        string $i18nLibrary = 'i18next',
        ?string $customAdapter = null,
        int $maxLineLength = 120,
    ): GeneratorConfig {
        return new GeneratorConfig(
            outputFormat: $outputFormat,
            enumOutputPath: $enumOutputPath,
            translationOutputPath: $translationOutputPath,
            i18nLibrary: $i18nLibrary,
            customAdapter: $customAdapter,
            maxLineLength: $maxLineLength,
        );
    }
}
