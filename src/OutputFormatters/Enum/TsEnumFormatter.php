<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\OutputFormatters\Enum;

use GaiaTools\TypeBridge\Contracts\OutputFormatter;
use GaiaTools\TypeBridge\Support\StringQuoter;
use GaiaTools\TypeBridge\ValueObjects\TransformedEnum;

final class TsEnumFormatter implements OutputFormatter
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
            if (is_string($case->value)) {
                $formattedValue = StringQuoter::quoteJs($case->value);
            } else {
                $formattedValue = $case->value;
            }
            $lines[] = sprintf(self::INDENT.'%s: %s,', $case->name, $formattedValue);
        }

        $lines[] = '} as const;';
        $lines[] = '';
        $lines[] = sprintf('export type %1$s = typeof %1$s[keyof typeof %1$s];', $transformed->name);
        $lines[] = '';

        return implode("\n", $lines);
    }

    public function getExtension(): string
    {
        return 'ts';
    }
}
