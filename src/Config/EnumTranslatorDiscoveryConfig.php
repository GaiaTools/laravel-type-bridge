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
        $cfg = config('type-bridge.enum_translators', []);

        return new self(
            enabled: (bool) ($cfg['enabled'] ?? true),
            discoveryPaths: (array) ($cfg['discovery_paths'] ?? ['app/Enums']),
            excludes: (array) ($cfg['excludes'] ?? []),
            outputPath: (string) ($cfg['output_path'] ?? 'js/composables/generated'),
            utilsComposablesPath: (string) ($cfg['utils_composables_path'] ?? 'js/composables'),
            utilsLibPath: (string) ($cfg['utils_lib_path'] ?? 'js/lib'),
        );
    }
}
