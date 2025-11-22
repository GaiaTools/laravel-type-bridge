<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Contracts;

interface Transformer
{
    /**
     * Transform source data into an intermediate representation.
     */
    public function transform(mixed $source): mixed;
}
