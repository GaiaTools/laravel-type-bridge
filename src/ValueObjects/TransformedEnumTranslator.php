<?php

namespace GaiaTools\TypeBridge\ValueObjects;

final readonly class TransformedEnumTranslator
{
    public function __construct(
        public string $name,
        public string $enumName,
        public string $translationKey,
        public string $enumImportPath,
        public string $outputPath,
    ) {}
}
