<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class GenerateEnum
{
    public function __construct(
        public bool $requiresComments = false,
        public bool $hasTranslator = false,
        public ?string $outputFormat = null,
        /** @var array<int,string> */
        public array $includeMethods = [],
    ) {}
}
