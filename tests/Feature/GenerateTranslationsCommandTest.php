<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Feature;

use GaiaTools\TypeBridge\Tests\TestCase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;

final class GenerateTranslationsCommandTest extends TestCase
{
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
}
