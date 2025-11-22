<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\OutputFormatters\Translation;

use GaiaTools\TypeBridge\Contracts\OutputFormatter;
use GaiaTools\TypeBridge\Support\JsObjectSerializer;
use GaiaTools\TypeBridge\ValueObjects\TransformedTranslation;

final class JsTranslationFormatter implements OutputFormatter
{
    public function format(mixed $transformed): string
    {
        assert($transformed instanceof TransformedTranslation);

        $locale = $transformed->locale;

        $object = JsObjectSerializer::serializeObject($transformed->data);

        return 'export const '.$locale.' = '.$object.';';
    }

    public function getExtension(): string
    {
        return 'js';
    }
}
