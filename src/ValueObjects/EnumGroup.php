<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\ValueObjects;

final readonly class EnumGroup
{
    public const KIND_ARRAY = 'array';

    public const KIND_RECORD = 'record';

    /**
     * @param  array<int,EnumGroupValue>|array<string,EnumGroupValue>  $values
     */
    public function __construct(
        public string $name,
        public string $kind,
        public array $values,
    ) {}
}
