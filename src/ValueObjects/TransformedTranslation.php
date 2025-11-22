<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\ValueObjects;

final readonly class TransformedTranslation
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public string $locale,
        public array $data,
        public bool $isFlat,
        public string $outputPath,
    ) {}
}
