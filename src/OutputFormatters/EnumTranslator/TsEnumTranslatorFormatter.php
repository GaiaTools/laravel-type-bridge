<?php

// src/OutputFormatters/EnumTranslator/TsEnumTranslatorFormatter.php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\OutputFormatters\EnumTranslator;

use GaiaTools\TypeBridge\ValueObjects\TransformedEnumTranslator;

final class TsEnumTranslatorFormatter extends AbstractEnumTranslatorFormatter
{
    protected function formatVueI18n(TransformedEnumTranslator $t): string
    {
        return <<<TS
import { useTranslator } from '@/composables/useTranslator';
import { {$t->enumName} } from '{$t->enumImportPath}';
import { createEnumTranslationMap } from '@/lib/createEnumTranslationMap';

export function {$t->name}() {
    const translations = createEnumTranslationMap({$t->enumName}, '{$t->translationKey}');
    return useTranslator(translations);
}

TS;
    }

    protected function formatI18next(TransformedEnumTranslator $t): string
    {
        return <<<TS
import { useTranslator } from '@/composables/useTranslator';
import { {$t->enumName} } from '{$t->enumImportPath}';
import { createEnumTranslationMap } from '@/lib/createEnumTranslationMap';

export function {$t->name}() {
    const translations = createEnumTranslationMap({$t->enumName}, '{$t->translationKey}');
    return useTranslator(translations);
}

TS;
    }

    protected function formatLaravel(TransformedEnumTranslator $t): string
    {
        return <<<TS
import { {$t->enumName} } from '{$t->enumImportPath}';
import { createEnumTranslationMap } from '@/lib/createEnumTranslationMap';
import { createTranslator } from '@/lib/createTranslator';

export const {$t->name} = createTranslator(
    createEnumTranslationMap({$t->enumName}, '{$t->translationKey}')
);

TS;
    }

    protected function formatVanilla(TransformedEnumTranslator $t): string
    {
        return <<<TS
import { {$t->enumName} } from '{$t->enumImportPath}';
import { createEnumTranslationMap } from '@/lib/createEnumTranslationMap';
import { createTranslator } from '@/lib/createTranslator';

export const {$t->name} = createTranslator(
    createEnumTranslationMap({$t->enumName}, '{$t->translationKey}')
);

TS;
    }

    public function getExtension(): string
    {
        return 'ts';
    }
}
