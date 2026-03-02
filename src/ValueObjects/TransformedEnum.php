<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\ValueObjects;

use Illuminate\Support\Collection;

final readonly class TransformedEnum
{
    /**
     * @param  Collection<int, EnumCase>  $cases
     * @param  Collection<int, EnumGroup>  $groups
     */
    public function __construct(
        public string $name,
        public Collection $cases,
        public string $namespace,
        public string $outputPath,
        public Collection $groups,
    ) {}
}
