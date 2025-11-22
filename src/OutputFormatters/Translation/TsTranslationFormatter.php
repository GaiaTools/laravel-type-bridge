<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\OutputFormatters\Translation;

use GaiaTools\TypeBridge\Contracts\OutputFormatter;
use GaiaTools\TypeBridge\Support\JsObjectSerializer;
use GaiaTools\TypeBridge\ValueObjects\TransformedTranslation;

final class TsTranslationFormatter implements OutputFormatter
{
    public function format(mixed $transformed): string
    {
        assert($transformed instanceof TransformedTranslation);

        $locale = $transformed->locale;

        $object = JsObjectSerializer::serializeObject($transformed->data);

        $lines = [];
        $lines[] = 'export const '.$locale.' = '.$object.' as const;';
        $lines[] = '';
        $lines[] = 'export type '.$locale.' = typeof '.$locale.';';

        return implode("\n", $lines);
    }

    public function getExtension(): string
    {
        return 'ts';
    }
}
