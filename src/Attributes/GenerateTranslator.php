<?php

namespace GaiaTools\TypeBridge\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class GenerateTranslator
{
    public function __construct(
        public ?string $translationKey = null,
        public bool $generateComposable = true
    ) {}
}
