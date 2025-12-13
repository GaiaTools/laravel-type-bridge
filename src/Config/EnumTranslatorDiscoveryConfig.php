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
        // Use Laravel's typed config helpers to avoid mixed offsets and ensure correct types
        $discoveryPathsRaw = config()->array(
            'type-bridge.enum_translators.discovery.include_paths',
            [app_path('Enums')]
        );
        /** @var array<int, string> $discoveryPaths */
        $discoveryPaths = array_values(array_filter(
            $discoveryPathsRaw,
            static fn ($value): bool => is_string($value)
        ));

        $excludesRaw = config()->array('type-bridge.enum_translators.discovery.exclude_paths', []);
        /** @var array<int, string> $excludes */
        $excludes = array_values(array_filter(
            $excludesRaw,
            static fn ($value): bool => is_string($value)
        ));

        $outputPath = config()->string(
            'type-bridge.enum_translators.translator_output_path',
            'js/composables/generated'
        );
        $utilsComposablesPath = config()->string(
            'type-bridge.enum_translators.utils_composables_output_path',
            'js/composables'
        );
        $utilsLibPath = config()->string(
            'type-bridge.enum_translators.utils_lib_output_path',
            'js/lib'
        );

        return new self(
            discoveryPaths: $discoveryPaths,
            excludes: $excludes,
            outputPath: $outputPath,
            utilsComposablesPath: $utilsComposablesPath,
            utilsLibPath: $utilsLibPath,
        );
    }
}
