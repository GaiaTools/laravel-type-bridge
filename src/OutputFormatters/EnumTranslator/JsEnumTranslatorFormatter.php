<?php

// src/OutputFormatters/EnumTranslator/JsEnumTranslatorFormatter.php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\OutputFormatters\EnumTranslator;

use GaiaTools\TypeBridge\ValueObjects\TransformedEnumTranslator;

final class JsEnumTranslatorFormatter extends AbstractEnumTranslatorFormatter
{
    protected function formatVueI18n(TransformedEnumTranslator $t): string
    {
        return <<<JS
import { useTranslator } from '@/composables/useTranslator';
import { {$t->enumName} } from '{$t->enumImportPath}';
import { createEnumTranslationMap } from '@/lib/createEnumTranslationMap';

/**
 * Translator composable for {$t->enumName}
 * @returns {Function} Translator function with utility methods
 */
export function {$t->name}() {
    const translations = createEnumTranslationMap({$t->enumName}, '{$t->translationKey}');
    return useTranslator(translations);
}

JS;
    }

    protected function formatI18next(TransformedEnumTranslator $t): string
    {
        return <<<JS
import { {$t->enumName} } from '{$t->enumImportPath}';
import { useEnumTranslator } from '@/hooks/useEnumTranslator';
import { createEnumTranslationMap } from '@/lib/createEnumTranslationMap';

/**
 * Translator hook for {$t->enumName}
 * @returns {Function} Translator function with utility methods
 */
export function {$t->name}() {
    const translations = createEnumTranslationMap({$t->enumName}, '{$t->translationKey}');
    return useEnumTranslator(translations);
}

JS;
    }

    protected function formatLaravel(TransformedEnumTranslator $t): string
    {
        return <<<JS
import { {$t->enumName} } from '{$t->enumImportPath}';
import { createEnumTranslationMap } from '@/lib/createEnumTranslationMap';
import { createTranslator } from '@/lib/createTranslator';

/**
 * Translator for {$t->enumName}
 */
export const {$t->name} = createTranslator(
    createEnumTranslationMap({$t->enumName}, '{$t->translationKey}')
);

JS;
    }

    protected function formatVanilla(TransformedEnumTranslator $t): string
    {
        return <<<JS
import { {$t->enumName} } from '{$t->enumImportPath}';
import { createEnumTranslationMap } from '@/lib/createEnumTranslationMap';
import { createTranslator } from '@/lib/createTranslator';

/**
 * Translator for {$t->enumName}
 */
export const {$t->name} = createTranslator(
    createEnumTranslationMap({$t->enumName}, '{$t->translationKey}')
);

JS;
    }

    public function getExtension(): string
    {
        return 'js';
    }
}