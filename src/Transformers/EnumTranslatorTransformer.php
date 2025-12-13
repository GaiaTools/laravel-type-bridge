<?php

namespace GaiaTools\TypeBridge\Transformers;

use GaiaTools\TypeBridge\Config\EnumTranslatorDiscoveryConfig;
use GaiaTools\TypeBridge\Contracts\Transformer;
use GaiaTools\TypeBridge\ValueObjects\TransformedEnumTranslator;
use ReflectionEnum;
use UnitEnum;

final class EnumTranslatorTransformer implements Transformer
{
    public function __construct(
        private readonly EnumTranslatorDiscoveryConfig $discoveryConfig,
    ) {}

    /**
     * @param  array{reflection: ReflectionEnum<UnitEnum>, translationKey: string}  $source
     */
    public function transform(mixed $source): TransformedEnumTranslator
    {
        // PHPStan knows the precise array shape from the PHPDoc; just read values.
        $reflection = $source['reflection'];
        $enumName = $reflection->getShortName();
        $composableName = "use{$enumName}Translator";

        // Build enum import path based on configured import base to mirror output path
        /** @var string $enumImportBase */
        $enumImportBase = config('type-bridge.enums.import_base', '@/enums/generated');
        $enumImportBase = rtrim($enumImportBase, '/');
        $enumImportPath = $enumImportBase.'/'.$enumName;

        return new TransformedEnumTranslator(
            name: $composableName,
            enumName: $enumName,
            translationKey: $source['translationKey'],
            enumImportPath: $enumImportPath,
            outputPath: resource_path($this->discoveryConfig->outputPath),
        );
    }
}
