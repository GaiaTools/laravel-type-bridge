<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Config;

final readonly class TranslationDiscoveryConfig
{
    public function __construct(
        public string $langPath,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            langPath: base_path('lang'),
        );
    }
}
