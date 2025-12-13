<?php

// src/OutputFormatters/EnumTranslator/JsEnumTranslatorFormatter.php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\OutputFormatters\EnumTranslator;

use GaiaTools\TypeBridge\ValueObjects\TransformedEnumTranslator;

final class JsEnumTranslatorFormatter extends AbstractEnumTranslatorFormatter
{
    protected function formatVueI18n(TransformedEnumTranslator $transformed): string
    {
        $composablesImportBase = rtrim(config()->string('type-bridge.enum_translators.utils_composables_import_path', '@/composables'), '/');
        $libImportBase = rtrim(config()->string('type-bridge.enum_translators.utils_lib_import_path', '@/lib'), '/');

        return <<<JS
import { useTranslator } from '{$composablesImportBase}/useTranslator';
import { {$transformed->enumName} } from '{$transformed->enumImportPath}';
import { createEnumTranslationMap } from '{$libImportBase}/createEnumTranslationMap';

/**
 * Translator composable for {$transformed->enumName}
 * @returns {Function} Translator function with utility methods
 */
export function {$transformed->name}() {
    const translations = createEnumTranslationMap({$transformed->enumName}, '{$transformed->translationKey}');
    return useTranslator(translations);
}

JS;
    }

    protected function formatI18next(TransformedEnumTranslator $transformed): string
    {
        $composablesImportBase = rtrim(config()->string('type-bridge.enum_translators.utils_composables_import_path', '@/composables'), '/');
        $libImportBase = rtrim(config()->string('type-bridge.enum_translators.utils_lib_import_path', '@/lib'), '/');

        return <<<JS
import { useTranslator } from '{$composablesImportBase}/useTranslator';
import { {$transformed->enumName} } from '{$transformed->enumImportPath}';
import { createEnumTranslationMap } from '{$libImportBase}/createEnumTranslationMap';

/**
 * Translator function for {$transformed->enumName}
 * @returns {Function} Translator function with utility methods
 */
export function {$transformed->name}() {
    const translations = createEnumTranslationMap({$transformed->enumName}, '{$transformed->translationKey}');
    return useTranslator(translations);
}

JS;
    }

    protected function formatLaravel(TransformedEnumTranslator $transformed): string
    {
        $libImportBase = rtrim(config()->string('type-bridge.enum_translators.utils_lib_import_path', '@/lib'), '/');

        return <<<JS
import { {$transformed->enumName} } from '{$transformed->enumImportPath}';
import { createEnumTranslationMap } from '{$libImportBase}/createEnumTranslationMap';
import { createTranslator } from '{$libImportBase}/createTranslator';

/**
 * Translator for {$transformed->enumName}
 */
export const {$transformed->name} = createTranslator(
    createEnumTranslationMap({$transformed->enumName}, '{$transformed->translationKey}')
);

JS;
    }

    protected function formatVanilla(TransformedEnumTranslator $transformed): string
    {
        $libImportBase = rtrim(config()->string('type-bridge.enum_translators.utils_lib_import_path', '@/lib'), '/');

        return <<<JS
import { {$transformed->enumName} } from '{$transformed->enumImportPath}';
import { createEnumTranslationMap } from '{$libImportBase}/createEnumTranslationMap';
import { createTranslator } from '{$libImportBase}/createTranslator';

/**
 * Translator for {$transformed->enumName}
 */
export const {$transformed->name} = createTranslator(
    createEnumTranslationMap({$transformed->enumName}, '{$transformed->translationKey}')
);

JS;
    }

    public function getExtension(): string
    {
        return 'js';
    }
}
