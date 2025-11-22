<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Contracts;

interface OutputFormatter
{
    /**
     * Format the transformed item into output string.
     */
    public function format(mixed $transformed): string;

    /**
     * Get the file extension for this formatter (e.g., 'ts', 'js').
     */
    public function getExtension(): string;
}
