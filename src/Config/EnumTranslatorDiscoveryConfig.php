<?php

namespace GaiaTools\TypeBridge\Config;

final readonly class EnumTranslatorDiscoveryConfig
{
    /**
     * @param  array<int,string>  $discoveryPaths
     * @param  array<int,string>  $excludes
     */
    public function __construct(
        public array $discoveryPaths,
        public array $excludes,
        public string $outputPath,
        public string $utilsComposablesPath,
        public string $utilsLibPath,
    ) {}

    public static function fromConfig(): self
    {
        /** @var array<string, mixed> $config */
        $config = config()->get('type-bridge.enum_translators');

        $discoveryPaths = (array) ($config['discovery']['include_paths'] ?? [app_path('Enums')]);

        $excludes = (array) ($config['discovery']['exclude_paths'] ?? []);

        $outputPath = $config['translator_output_path'] ?? 'js/composables/generated';
        $utilsComposablesPath = $config['utils_composables_output_path'] ?? 'js/composables';
        $utilsLibPath = $config['utils_lib_output_path'] ?? 'js/lib';

        return new self(
            discoveryPaths: $discoveryPaths,
            excludes: $excludes,
            outputPath: is_string($outputPath) ? $outputPath : 'js/composables/generated',
            utilsComposablesPath: is_string($utilsComposablesPath) ? $utilsComposablesPath : 'js/composables',
            utilsLibPath: is_string($utilsLibPath) ? $utilsLibPath : 'js/lib',
        );
    }
}
