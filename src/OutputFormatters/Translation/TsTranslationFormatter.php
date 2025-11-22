<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\OutputFormatters\Translation;

use GaiaTools\TypeBridge\Contracts\OutputFormatter;
use GaiaTools\TypeBridge\ValueObjects\TransformedTranslation;

final class TsTranslationFormatter implements OutputFormatter
{
    public function format(mixed $transformed): string
    {
        assert($transformed instanceof TransformedTranslation);

        $locale = $transformed->locale;

        $jsonPretty = json_encode(
            $transformed->data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        $objectLines = $jsonPretty !== false ? explode("\n", (string) $jsonPretty) : ['{}'];

        $lines = [];
        $lines[] = 'export const '.$locale.' = '.$objectLines[0];
        for ($i = 1; $i < count($objectLines); $i++) {
            $lines[] = $objectLines[$i];
        }
        $last = count($lines) - 1;
        $lines[$last] = rtrim($lines[$last]).' as const;';
        $lines[] = '';
        $lines[] = 'export type '.$locale.' = typeof '.$locale.';';

        return implode("\n", $lines);
    }

    public function getExtension(): string
    {
        return 'ts';
    }
}
