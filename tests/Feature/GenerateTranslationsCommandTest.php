<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Feature;

use GaiaTools\TypeBridge\Tests\TestCase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;

final class GenerateTranslationsCommandTest extends TestCase
{
    #[Test]
    public function it_discovers_locales_when_not_provided_and_generates_one_file_per_locale(): void
    {
        $langPath = $this->app->langPath();

        // Ensure at least two locale directories exist: 'en' from setup and an extra 'fr'
        if (! File::isDirectory($langPath.'/fr')) {
            File::makeDirectory($langPath.'/fr', 0755, true);
        }

        // Add a non-existent path to ensure it is safely skipped by discovery
        $this->app['config']->set('type-bridge.translations.discovery.include_paths', [
            resource_path('non-existent-lang-root'),
            $langPath,
        ]);

        // Run command without locale argument so it must discover locales
        $this->artisan('type-bridge:translations')
            ->expectsOutputToContain('Generating translations...')
            ->expectsOutputToContain('Generated')
            ->assertExitCode(0);

        $outputDir = resource_path('test-output/translations');
        $this->assertDirectoryExists($outputDir);

        $files = File::files($outputDir);
        // Should generate at least "en" and "fr"
        $this->assertGreaterThanOrEqual(2, count($files));

        $names = array_map(static fn ($f) => $f->getFilename(), $files);
        $this->assertContains('en.ts', $names);
        $this->assertContains('fr.ts', $names);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Create test translation files
        $langPath = base_path('lang/en');
        File::ensureDirectoryExists($langPath);
        File::put($langPath.'/messages.php', "<?php\nreturn ['hello' => 'Hello World'];");
    }

    #[Test]
    public function it_generates_translations_with_locale_argument(): void
    {
        $this->artisan('type-bridge:translations', ['locale' => 'en'])
            ->expectsOutputToContain('Generating translations...')
            ->expectsOutputToContain('Generated')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_generates_translations_with_flat_option(): void
    {
        $this->artisan('type-bridge:translations', ['locale' => 'en', '--flat' => true])
            ->expectsOutputToContain('Generating translations...')
            ->expectsOutputToContain('Generated')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_generates_translations_with_json_format(): void
    {
        $this->artisan('type-bridge:translations', ['locale' => 'en', '--format' => 'json'])
            ->expectsOutputToContain('Generating translations...')
            ->expectsOutputToContain('Generated')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_generates_translations_with_js_format(): void
    {
        $this->artisan('type-bridge:translations', ['locale' => 'en', '--format' => 'js'])
            ->expectsOutputToContain('Generating translations...')
            ->expectsOutputToContain('Generated')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_generates_translations_with_ts_format(): void
    {
        $this->artisan('type-bridge:translations', ['locale' => 'en', '--format' => 'ts'])
            ->expectsOutputToContain('Generating translations...')
            ->expectsOutputToContain('Generated')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_respects_provided_locale_and_flat_option_and_generates_single_file(): void
    {
        // Provide explicit locale and --flat option; should only create one file
        $this->artisan('type-bridge:translations', [
            'locale' => 'en',
            '--flat' => true,
        ])
            ->expectsOutputToContain('Generating translations...')
            ->expectsOutputToContain('Generated')
            ->assertExitCode(0);

        $outputDir = resource_path('test-output/translations');
        $this->assertDirectoryExists($outputDir);

        $files = File::files($outputDir);
        $this->assertCount(1, $files);

        $file = $files[0];
        $this->assertSame('en.ts', $file->getFilename());
        $contents = File::get($file->getPathname());
        // Flat option should result in dot-notated keys
        $this->assertStringContainsString('"messages.hello"', $contents);
        $this->assertStringContainsString('"validation.required"', $contents);
    }

    #[Test]
    public function it_skips_non_existent_lang_root_and_still_generates_from_existing_paths(): void
    {
        // Point discovery to a path that does not exist to hit the `continue` branch (line 87)
        // and also include a valid lang path so generation proceeds normally
        $this->app['config']->set('type-bridge.translations.discovery.include_paths', [
            resource_path('non-existent-lang-root'),
            base_path('lang'),
        ]);

        $this->artisan('type-bridge:translations')
            ->expectsOutputToContain('Generating translations...')
            ->expectsOutputToContain('Generated')
            ->assertExitCode(0);

        // Files should have been produced from the existing path despite the skipped one
        $outputDir = resource_path('test-output/translations');
        $this->assertDirectoryExists($outputDir);
        $files = File::exists($outputDir) ? File::files($outputDir) : [];
        $this->assertGreaterThanOrEqual(1, is_countable($files) ? count($files) : 0);
    }
}
