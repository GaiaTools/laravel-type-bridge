<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\OutputFormatters\EnumTranslator;

use GaiaTools\TypeBridge\ValueObjects\TransformedEnumTranslator;

final class TsEnumTranslatorFormatter extends AbstractEnumTranslatorFormatter
{
    private const DEFAULT_UTILS_LIB_IMPORT_PATH = '@/lib';

    private const DEFAULT_COMPOSABLES_IMPORT_PATH = '@/composables';

    /**
     * Shared engine-driven formatter implementation for TypeScript.
     * Generates code using useTranslator with translation keys.
     *
     * @param  string  $docType  Unused in TypeScript (no JSDoc differentiation)
     * @param  string  $engine  Engine symbol name (unused in lean output; engine is configured at app setup)
     */
    protected function formatWithEngine(
        TransformedEnumTranslator $transformed,
        string $docType,
        string $engine
    ): string {
        $composablesImportBase = rtrim(
            config()->string(
                'type-bridge.enum_translators.utils_composables_import_path',
                self::DEFAULT_COMPOSABLES_IMPORT_PATH
            ),
            '/'
        );
        $libImportBase = rtrim(
            config()->string(
                'type-bridge.enum_translators.utils_lib_import_path',
                self::DEFAULT_UTILS_LIB_IMPORT_PATH
            ),
            '/'
        );

        return <<<TS
import { useTranslator } from '{$composablesImportBase}/useTranslator';
import { {$transformed->enumName} } from '{$transformed->enumImportPath}';
import { createEnumTranslationMap } from '{$libImportBase}/createEnumTranslationMap';

export function {$transformed->name}() {
    const translations = createEnumTranslationMap({$transformed->enumName}, '{$transformed->translationKey}');
    return useTranslator(translations);
}

TS;
    }

    public function getExtension(): string
    {
        return 'ts';
    }
}
