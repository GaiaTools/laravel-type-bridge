<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\ValueObjects;

final readonly class EnumGroupValue
{
    public const KIND_ENUM = 'enum';

    public const KIND_LITERAL = 'literal';

    public function __construct(
        public string $kind,
        public string|int|float|bool|null $value,
    ) {}
}
