<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge;

use GaiaTools\TypeBridge\Adapters\I18nextSyntaxAdapter;
use GaiaTools\TypeBridge\Adapters\LaravelSyntaxAdapter;
use GaiaTools\TypeBridge\Adapters\VueI18nSyntaxAdapter;
use GaiaTools\TypeBridge\Console\Commands\GenerateEnumsCommand;
use GaiaTools\TypeBridge\Console\Commands\GenerateEnumTranslatorsCommand;
use GaiaTools\TypeBridge\Console\Commands\GenerateTranslationsCommand;
use GaiaTools\TypeBridge\Console\Commands\PublishConfigCommand;
use GaiaTools\TypeBridge\Console\Commands\PublishEnumTranslatorUtilsCommand;
use GaiaTools\TypeBridge\Contracts\FileEnumerator;
use GaiaTools\TypeBridge\Contracts\TranslationSyntaxAdapter;
use GaiaTools\TypeBridge\Support\EnforcingFileEnumerator;
use GaiaTools\TypeBridge\Support\RecursiveFileEnumerator;
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
                GenerateEnumTranslatorsCommand::class, // Add this
                PublishConfigCommand::class,
                PublishEnumTranslatorUtilsCommand::class, // And this if not already added
            ]);
        }
    }

    public function register(): void
    {
        $this->app->bind(FileEnumerator::class, function () {
            return new EnforcingFileEnumerator(
                new RecursiveFileEnumerator
            );
        });

        $this->mergeConfigFrom(
            __DIR__.'/../config/type-bridge.php',
            'type-bridge'
        );

        $this->app->singleton(TranslationSyntaxAdapter::class, function ($app) {
            $library = config()->string('type-bridge.i18n.library', 'i18next');

            /** @var string|null $customAdapter */
            $customAdapter = config('type-bridge.i18n.custom_adapter');

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
