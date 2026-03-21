<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Contracts;

use GaiaTools\TypeBridge\ValueObjects\GeneratedFile;
use Illuminate\Support\Collection;

interface BridgeGenerator
{
    /**
     * @return Collection<int, GeneratedFile>
     */
    public function generate(): Collection;

    public function getName(): string;
}
