<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Config;

final class EnumDiscoveryConfig
{
    /**
     * @param  array<int, string>  $paths
     * @param  array<int, string>  $excludes
     */
    public function __construct(
        public array $paths,
        public bool $generateBackedEnums,
        public array $excludes = [],
    ) {}

    public static function fromConfig(): self
    {
        $rawPaths = (array) config('type-bridge.enums.discovery.include_paths', [app_path('Enums')]);
        $rawExcludes = (array) config('type-bridge.enums.discovery.exclude_paths', []);

        /** @var list<string> $paths */
        $paths = array_values(array_filter($rawPaths, static fn ($v): bool => is_string($v)));
        /** @var list<string> $excludes */
        $excludes = array_values(array_filter($rawExcludes, static fn ($v): bool => is_string($v)));

        return new self(
            paths: $paths,
            generateBackedEnums: (bool) config('type-bridge.enums.generate_backed_enums', false),
            excludes: $excludes,
        );
    }
}
