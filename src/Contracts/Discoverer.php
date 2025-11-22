<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Contracts;

use Illuminate\Support\Collection;

interface Discoverer
{
    /**
     * Discover items to be transformed.
     *
     * @return Collection<int, mixed>
     */
    public function discover(): Collection;
}
