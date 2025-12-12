<?php

// src/OutputFormatters/EnumTranslator/TsEnumTranslatorFormatter.php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\OutputFormatters\EnumTranslator;

use GaiaTools\TypeBridge\ValueObjects\TransformedEnumTranslator;

final class TsEnumTranslatorFormatter extends AbstractEnumTranslatorFormatter
{
    protected function formatVueI18n(TransformedEnumTranslator $transformed): string
    {
        return <<<TS
import { useTranslator } from '@/composables/useTranslator';
import { {$transformed->enumName} } from '{$transformed->enumImportPath}';
import { createEnumTranslationMap } from '@/lib/createEnumTranslationMap';

export function {$transformed->name}() {
    const translations = createEnumTranslationMap({$transformed->enumName}, '{$transformed->translationKey}');
    return useTranslator(translations);
}

TS;
    }

    protected function formatI18next(TransformedEnumTranslator $transformed): string
    {
        return <<<TS
import { useTranslator } from '@/composables/useTranslator';
import { {$transformed->enumName} } from '{$transformed->enumImportPath}';
import { createEnumTranslationMap } from '@/lib/createEnumTranslationMap';

export function {$transformed->name}() {
    const translations = createEnumTranslationMap({$transformed->enumName}, '{$transformed->translationKey}');
    return useTranslator(translations);
}

TS;
    }

    protected function formatLaravel(TransformedEnumTranslator $transformed): string
    {
        return <<<TS
import { {$transformed->enumName} } from '{$transformed->enumImportPath}';
import { createEnumTranslationMap } from '@/lib/createEnumTranslationMap';
import { createTranslator } from '@/lib/createTranslator';

export const {$transformed->name} = createTranslator(
    createEnumTranslationMap({$transformed->enumName}, '{$transformed->translationKey}')
);

TS;
    }

    protected function formatVanilla(TransformedEnumTranslator $transformed): string
    {
        return <<<TS
import { {$transformed->enumName} } from '{$transformed->enumImportPath}';
import { createEnumTranslationMap } from '@/lib/createEnumTranslationMap';
import { createTranslator } from '@/lib/createTranslator';

export const {$transformed->name} = createTranslator(
    createEnumTranslationMap({$transformed->enumName}, '{$transformed->translationKey}')
);

TS;
    }

    public function getExtension(): string
    {
        return 'ts';
    }
}
