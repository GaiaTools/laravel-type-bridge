<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\OutputFormatters\Enum;

use GaiaTools\TypeBridge\Contracts\OutputFormatter;
use GaiaTools\TypeBridge\ValueObjects\TransformedEnum;

final class JsEnumFormatter implements OutputFormatter
{
    private const INDENT = '    ';

    public function format(mixed $transformed): string
    {
        assert($transformed instanceof TransformedEnum);

        $lines = [];
        $lines[] = sprintf('export const %s = {', $transformed->name);

        foreach ($transformed->cases as $case) {
            if ($case->docComment) {
                $lines[] = self::INDENT.$case->docComment;
            }
            $formattedValue = is_string($case->value) ? "'{$case->value}'" : $case->value;
            $lines[] = sprintf(self::INDENT.'%s: %s,', $case->name, $formattedValue);
        }

        $lines[] = '};';
        $lines[] = '';

        return implode("\n", $lines);
    }

    public function getExtension(): string
    {
        return 'js';
    }
}
