<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Config;

final readonly class GeneratorConfig
{
    public function __construct(
        public string $outputFormat,
        public string $enumOutputPath,
        public string $translationOutputPath,
        public string $i18nLibrary,
        public ?string $customAdapter,
        public int $maxLineLength,
    ) {}

    public static function fromConfig(): self
    {
        $outputFormat = config()->string('type-bridge.output_format', 'ts');

        $enumOutputPath = config()->string('type-bridge.enums.output_path', 'js/enums/generated');

        $translationOutputPath = config()->string('type-bridge.translations.output_path', 'js/locales/generated');

        $i18nLibrary = config()->string('type-bridge.i18n_library', 'i18next');

        /** @var string|null $customAdapter */
        $customAdapter = config('type-bridge.translations.custom_adapter');

        $maxLineLength = config()->integer('type-bridge.max_line_length', 120);

        return new self(
            outputFormat: $outputFormat,
            enumOutputPath: $enumOutputPath,
            translationOutputPath: $translationOutputPath,
            i18nLibrary: $i18nLibrary,
            customAdapter: $customAdapter,
            maxLineLength: $maxLineLength,
        );
    }
}
