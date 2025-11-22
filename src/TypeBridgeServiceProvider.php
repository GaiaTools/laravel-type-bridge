<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge;

use GaiaTools\TypeBridge\Adapters\I18nextSyntaxAdapter;
use GaiaTools\TypeBridge\Adapters\LaravelSyntaxAdapter;
use GaiaTools\TypeBridge\Adapters\VueI18nSyntaxAdapter;
use GaiaTools\TypeBridge\Console\Commands\GenerateEnumsCommand;
use GaiaTools\TypeBridge\Console\Commands\GenerateTranslationsCommand;
use GaiaTools\TypeBridge\Console\Commands\PublishConfigCommand;
use GaiaTools\TypeBridge\Contracts\TranslationSyntaxAdapter;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class TypeBridgeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/type-bridge.php' => config_path('type-bridge.php'),
            ], 'type-bridge-config');

            $this->commands([
                GenerateEnumsCommand::class,
                GenerateTranslationsCommand::class,
                PublishConfigCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/type-bridge.php',
            'type-bridge'
        );

        $this->app->singleton(TranslationSyntaxAdapter::class, function ($app) {
            $library = config()->string('type-bridge.translations.i18n_library', 'i18next');

            /** @var string|null $customAdapter */
            $customAdapter = config('type-bridge.translations.custom_adapter');

            if ($customAdapter !== null && class_exists($customAdapter)) {
                return $app->make($customAdapter);
            }

            return match ($library) {
                'i18next' => new I18nextSyntaxAdapter,
                'vue-i18n' => new VueI18nSyntaxAdapter,
                'laravel' => new LaravelSyntaxAdapter,
                default => throw new InvalidArgumentException(
                    "Unknown i18n library: {$library}. Supported: i18next, vue-i18n, laravel"
                ),
            };
        });
    }
}
