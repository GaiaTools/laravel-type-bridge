<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\OutputFormatters\Translation;

use GaiaTools\TypeBridge\Contracts\OutputFormatter;
use GaiaTools\TypeBridge\ValueObjects\TransformedTranslation;

final class JsTranslationFormatter implements OutputFormatter
{
    public function format(mixed $transformed): string
    {
        assert($transformed instanceof TransformedTranslation);

        $locale = $transformed->locale;
        $jsonPretty = json_encode(
            $transformed->data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        $lines = [];
        $lines[] = 'export const '.$locale.' = '.$jsonPretty.';';

        return implode("\n", $lines);
    }

    public function getExtension(): string
    {
        return 'js';
    }
}
