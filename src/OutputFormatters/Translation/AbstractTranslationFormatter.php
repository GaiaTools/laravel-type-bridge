<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\OutputFormatters\Translation;

use GaiaTools\TypeBridge\Contracts\OutputFormatter;
use GaiaTools\TypeBridge\Support\JsObjectSerializer;
use GaiaTools\TypeBridge\ValueObjects\TransformedTranslation;

abstract class AbstractTranslationFormatter implements OutputFormatter
{
    public function format(mixed $transformed): string
    {
        assert($transformed instanceof TransformedTranslation);

        $locale = $transformed->locale;
        $trailingComma = config()->boolean('type-bridge.trailing_commas', true);

        $object = JsObjectSerializer::serializeObject($transformed->data, 0, $trailingComma);

        return $this->formatOutput($locale, $object);
    }

    abstract protected function formatOutput(string $locale, string $serializedObject): string;
}
