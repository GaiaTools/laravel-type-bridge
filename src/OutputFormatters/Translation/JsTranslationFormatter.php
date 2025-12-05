<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\OutputFormatters\Translation;

final class JsTranslationFormatter extends AbstractTranslationFormatter
{
    protected function formatOutput(string $locale, string $serializedObject): string
    {
        return 'export const '.$locale.' = '.$serializedObject.';';
    }

    public function getExtension(): string
    {
        return 'js';
    }
}
