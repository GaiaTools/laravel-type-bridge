<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\OutputFormatters\Translation;

use GaiaTools\TypeBridge\Contracts\OutputFormatter;
use GaiaTools\TypeBridge\ValueObjects\TransformedTranslation;

final class JsonTranslationFormatter implements OutputFormatter
{
    public function format(mixed $transformed): string
    {
        assert($transformed instanceof TransformedTranslation);

        return json_encode(
            $transformed->data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ) ?: '{}';
    }

    public function getExtension(): string
    {
        return 'json';
    }
}
