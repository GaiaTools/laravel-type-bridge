<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\OutputFormatters\Enum;

use GaiaTools\TypeBridge\Contracts\OutputFormatter;
use GaiaTools\TypeBridge\Support\StringQuoter;
use GaiaTools\TypeBridge\ValueObjects\TransformedEnum;

final class JsEnumFormatter implements OutputFormatter
{
    private const INDENT = '    ';

    public function format(mixed $transformed): string
    {
        assert($transformed instanceof TransformedEnum);

        $trailingComma = config('type-bridge.trailing_commas', true);

        $lines = [];
        $lines[] = sprintf('export const %s = {', $transformed->name);

        $cases = $transformed->cases;
        $lastIndex = count($cases) - 1;

        foreach ($cases as $i => $case) {
            if ($case->docComment) {
                $lines[] = self::INDENT.$case->docComment;
            }
            if (is_string($case->value)) {
                $formattedValue = StringQuoter::quoteJs($case->value);
            } else {
                $formattedValue = $case->value;
            }
            $comma = ($i === $lastIndex && ! $trailingComma) ? '' : ',';
            $lines[] = sprintf(self::INDENT.'%s: %s%s', $case->name, $formattedValue, $comma);
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
