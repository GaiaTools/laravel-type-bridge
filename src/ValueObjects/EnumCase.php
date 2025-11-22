<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\ValueObjects;

final readonly class EnumCase
{
    public function __construct(
        public string $name,
        public string|int $value,
        public ?string $docComment = null,
    ) {}
}
