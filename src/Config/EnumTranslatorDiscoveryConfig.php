<?php

namespace GaiaTools\TypeBridge\Config;

final readonly class EnumTranslatorDiscoveryConfig
{
    /**
     * @param  array<int,string>  $discoveryPaths
     * @param  array<int,string>  $excludes
     */
    public function __construct(
        public bool $enabled,
        public array $discoveryPaths,
        public array $excludes,
        public string $outputPath,
        public string $utilsComposablesPath,
        public string $utilsLibPath,
    ) {}

    public static function fromConfig(): self
    {
        /** @var array<string, mixed> $config */
        $config = config()->array('type-bridge.enum_translators', []);

        $discoveryPaths = [];
        foreach ((array) ($config['discovery_paths'] ?? ['app/Enums']) as $value) {
            if (is_string($value)) {
                $discoveryPaths[] = $value;
            }
        }

        $excludes = [];
        foreach ((array) ($config['excludes'] ?? []) as $value) {
            if (is_string($value)) {
                $excludes[] = $value;
            }
        }

        $outputPath = $config['output_path'] ?? 'js/composables/generated';
        $utilsComposablesPath = $config['utils_composables_path'] ?? 'js/composables';
        $utilsLibPath = $config['utils_lib_path'] ?? 'js/lib';

        return new self(
            enabled: (bool) ($config['enabled'] ?? true),
            discoveryPaths: $discoveryPaths,
            excludes: $excludes,
            outputPath: is_string($outputPath) ? $outputPath : 'js/composables/generated',
            utilsComposablesPath: is_string($utilsComposablesPath) ? $utilsComposablesPath : 'js/composables',
            utilsLibPath: is_string($utilsLibPath) ? $utilsLibPath : 'js/lib',
        );
    }
}
