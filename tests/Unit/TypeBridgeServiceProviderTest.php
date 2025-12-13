<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit;

use GaiaTools\TypeBridge\Adapters\I18nextSyntaxAdapter;
use GaiaTools\TypeBridge\Adapters\LaravelSyntaxAdapter;
use GaiaTools\TypeBridge\Adapters\VueI18nSyntaxAdapter;
use GaiaTools\TypeBridge\Console\Commands\GenerateEnumsCommand;
use GaiaTools\TypeBridge\Console\Commands\GenerateTranslationsCommand;
use GaiaTools\TypeBridge\Console\Commands\PublishConfigCommand;
use GaiaTools\TypeBridge\Contracts\TranslationSyntaxAdapter;
use GaiaTools\TypeBridge\Tests\TestCase;
use GaiaTools\TypeBridge\TypeBridgeServiceProvider;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;

class TypeBridgeServiceProviderTest extends TestCase
{
    #[Test]
    public function register_binds_default_i18next_adapter(): void
    {
        // default is i18next per provider
        $this->app['config']->set('type-bridge.i18n_library', 'i18next');
        $this->app['config']->set('type-bridge.translations.custom_adapter', null);

        $resolved = $this->app->make(TranslationSyntaxAdapter::class);

        $this->assertInstanceOf(I18nextSyntaxAdapter::class, $resolved);
        $this->assertSame('i18next', $resolved->getTargetLibrary());
    }

    #[Test]
    public function register_binds_vue_i18n_adapter_when_configured(): void
    {
        $this->app['config']->set('type-bridge.i18n_library', 'vue-i18n');
        $this->app['config']->set('type-bridge.translations.custom_adapter', null);

        $resolved = $this->app->make(TranslationSyntaxAdapter::class);

        $this->assertInstanceOf(VueI18nSyntaxAdapter::class, $resolved);
        $this->assertSame('vue-i18n', $resolved->getTargetLibrary());
    }

    #[Test]
    public function register_binds_laravel_adapter_when_configured(): void
    {
        $this->app['config']->set('type-bridge.i18n_library', 'laravel');
        $this->app['config']->set('type-bridge.translations.custom_adapter', null);

        $resolved = $this->app->make(TranslationSyntaxAdapter::class);

        $this->assertInstanceOf(LaravelSyntaxAdapter::class, $resolved);
        $this->assertSame('laravel', $resolved->getTargetLibrary());
    }

    #[Test]
    public function register_uses_custom_adapter_class_when_provided(): void
    {
        $customClass = get_class(new class implements TranslationSyntaxAdapter
        {
            public function transform(array $translations): array
            {
                return $translations;
            }

            public function getTargetLibrary(): string
            {
                return 'custom-lib';
            }
        });

        // Even if library set otherwise, custom_adapter takes precedence
        $this->app['config']->set('type-bridge.i18n_library', 'i18next');
        $this->app['config']->set('type-bridge.translations.custom_adapter', $customClass);

        $resolved = $this->app->make(TranslationSyntaxAdapter::class);

        $this->assertInstanceOf($customClass, $resolved);
        $this->assertSame('custom-lib', $resolved->getTargetLibrary());
    }

    #[Test]
    public function register_throws_for_unknown_library(): void
    {
        $this->app['config']->set('type-bridge.i18n_library', 'unknown-lib');
        $this->app['config']->set('type-bridge.translations.custom_adapter', null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown i18n library: unknown-lib');

        // Resolving the singleton triggers the match/exception
        $this->app->make(TranslationSyntaxAdapter::class);
    }

    #[Test]
    public function boot_publishes_config_and_registers_commands_when_in_console(): void
    {
        // Ensure provider boot has been executed for console context
        // Testbench runs in console mode by default, and our provider is registered via TestCase
        /** @var array<string, string> $paths */
        $paths = BaseServiceProvider::pathsToPublish(TypeBridgeServiceProvider::class, 'type-bridge-config');

        // Publish mapping should include our config file
        $this->assertNotEmpty($paths);
        $this->assertContains(config_path('type-bridge.php'), array_values($paths));

        // Commands should be registered
        $allCommands = Artisan::all();

        $this->assertArrayHasKey('type-bridge:enums', $allCommands);
        $this->assertArrayHasKey('type-bridge:translations', $allCommands);
        $this->assertArrayHasKey('type-bridge:publish', $allCommands);

        $this->assertInstanceOf(GenerateEnumsCommand::class, $allCommands['type-bridge:enums']);
        $this->assertInstanceOf(GenerateTranslationsCommand::class, $allCommands['type-bridge:translations']);
        $this->assertInstanceOf(PublishConfigCommand::class, $allCommands['type-bridge:publish']);
    }
}
