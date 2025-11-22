<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Adapters;

use GaiaTools\TypeBridge\Contracts\TranslationSyntaxAdapter;

/**
 * Passthrough adapter that preserves Laravel syntax
 * Useful to manually handle transformations
 */
final class LaravelSyntaxAdapter implements TranslationSyntaxAdapter
{
    public function transform(array $translations): array
    {
        return $translations;
    }

    public function getTargetLibrary(): string
    {
        return 'laravel';
    }
}
