<?php

// src/OutputFormatters/EnumTranslator/TsEnumTranslatorFormatter.php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\OutputFormatters\EnumTranslator;

use GaiaTools\TypeBridge\ValueObjects\TransformedEnumTranslator;

final class TsEnumTranslatorFormatter extends AbstractEnumTranslatorFormatter
{
    protected function formatVueI18n(TransformedEnumTranslator $transformed): string
    {
        $composablesImportBase = rtrim((string) config('type-bridge.enum_translators.utils_composables_import_path', '@/composables'), '/');
        $libImportBase = rtrim((string) config('type-bridge.enum_translators.utils_lib_import_path', '@/lib'), '/');

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

    protected function formatI18next(TransformedEnumTranslator $transformed): string
    {
        $composablesImportBase = rtrim((string) config('type-bridge.enum_translators.utils_composables_import_path', '@/composables'), '/');
        $libImportBase = rtrim((string) config('type-bridge.enum_translators.utils_lib_import_path', '@/lib'), '/');

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

    protected function formatLaravel(TransformedEnumTranslator $transformed): string
    {
        $libImportBase = rtrim((string) config('type-bridge.enum_translators.utils_lib_import_path', '@/lib'), '/');

        return <<<TS
import { {$transformed->enumName} } from '{$transformed->enumImportPath}';
import { createEnumTranslationMap } from '{$libImportBase}/createEnumTranslationMap';
import { createTranslator } from '{$libImportBase}/createTranslator';

export const {$transformed->name} = createTranslator(
    createEnumTranslationMap({$transformed->enumName}, '{$transformed->translationKey}')
);

TS;
    }

    protected function formatVanilla(TransformedEnumTranslator $transformed): string
    {
        $libImportBase = rtrim((string) config('type-bridge.enum_translators.utils_lib_import_path', '@/lib'), '/');

        return <<<TS
import { {$transformed->enumName} } from '{$transformed->enumImportPath}';
import { createEnumTranslationMap } from '{$libImportBase}/createEnumTranslationMap';
import { createTranslator } from '{$libImportBase}/createTranslator';

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
