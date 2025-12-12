<?php

// src/OutputFormatters/EnumTranslator/JsEnumTranslatorFormatter.php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\OutputFormatters\EnumTranslator;

use GaiaTools\TypeBridge\ValueObjects\TransformedEnumTranslator;

final class JsEnumTranslatorFormatter extends AbstractEnumTranslatorFormatter
{
    protected function formatVueI18n(TransformedEnumTranslator $transformed): string
    {
        return <<<JS
import { useTranslator } from '@/composables/useTranslator';
import { {$transformed->enumName} } from '{$transformed->enumImportPath}';
import { createEnumTranslationMap } from '@/lib/createEnumTranslationMap';

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
        return <<<JS
import { {$transformed->enumName} } from '{$transformed->enumImportPath}';
import { useEnumTranslator } from '@/hooks/useEnumTranslator';
import { createEnumTranslationMap } from '@/lib/createEnumTranslationMap';

/**
 * Translator hook for {$transformed->enumName}
 * @returns {Function} Translator function with utility methods
 */
export function {$transformed->name}() {
    const translations = createEnumTranslationMap({$transformed->enumName}, '{$transformed->translationKey}');
    return useEnumTranslator(translations);
}

JS;
    }

    protected function formatLaravel(TransformedEnumTranslator $transformed): string
    {
        return <<<JS
import { {$transformed->enumName} } from '{$transformed->enumImportPath}';
import { createEnumTranslationMap } from '@/lib/createEnumTranslationMap';
import { createTranslator } from '@/lib/createTranslator';

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
        return <<<JS
import { {$transformed->enumName} } from '{$transformed->enumImportPath}';
import { createEnumTranslationMap } from '@/lib/createEnumTranslationMap';
import { createTranslator } from '@/lib/createTranslator';

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
