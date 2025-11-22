<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Contracts;

use Illuminate\Support\Collection;

interface BridgeGenerator
{
    /**
     * @return Collection<int, \GaiaTools\TypeBridge\ValueObjects\GeneratedFile>
     */
    public function generate(): Collection;

    public function getName(): string;
}
