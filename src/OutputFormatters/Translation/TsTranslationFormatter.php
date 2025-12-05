<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\OutputFormatters\Translation;

final class TsTranslationFormatter extends AbstractTranslationFormatter
{
    protected function formatOutput(string $locale, string $serializedObject): string
    {
        $lines = [];
        $lines[] = 'export const '.$locale.' = '.$serializedObject.' as const;';
        $lines[] = '';
        $lines[] = 'export type '.$locale.' = typeof '.$locale.';';

        return implode("\n", $lines);
    }

    public function getExtension(): string
    {
        return 'ts';
    }
}
