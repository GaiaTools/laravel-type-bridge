<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Discoverers;

use GaiaTools\TypeBridge\Contracts\Discoverer;
use Illuminate\Support\Collection;

final class SimpleDiscoverer implements Discoverer
{
    public function __construct(
        private readonly mixed $items,
    ) {}

    /**
     * @return Collection<int, mixed>
     */
    public function discover(): Collection
    {
        // If it's not an array, wrap it
        if (! is_array($this->items)) {
            $col = collect([$this->items])->map(static fn ($v): mixed => $v);

            /** @var Collection<int, mixed> $col */
            return $col;
        }

        // If it's an associative array (has string keys), treat as single item
        if (array_keys($this->items) !== range(0, count($this->items) - 1)) {
            $col = collect([$this->items])->map(static fn ($v): mixed => $v);

            /** @var Collection<int, mixed> $col */
            return $col;
        }

        // Otherwise it's a list of items
        $col = collect($this->items)->map(static fn ($v): mixed => $v);

        /** @var Collection<int, mixed> $col */
        return $col;
    }
}
