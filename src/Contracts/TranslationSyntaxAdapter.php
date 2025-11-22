<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Contracts;

interface TranslationSyntaxAdapter
{
    /**
     * Transform Laravel translation syntax to target i18n library syntax
     *
     * @param  array<string, mixed>  $translations
     * @return array<string, mixed>
     */
    public function transform(array $translations): array;

    /**
     * Get the name of the target i18n library
     */
    public function getTargetLibrary(): string;
}
