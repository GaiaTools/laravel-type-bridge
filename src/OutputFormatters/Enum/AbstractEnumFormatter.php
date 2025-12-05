<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\OutputFormatters\Enum;

use GaiaTools\TypeBridge\Contracts\OutputFormatter;
use GaiaTools\TypeBridge\Support\StringQuoter;
use GaiaTools\TypeBridge\ValueObjects\TransformedEnum;

abstract class AbstractEnumFormatter implements OutputFormatter
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

        $this->addClosingLines($lines, $transformed->name);

        return implode("\n", $lines);
    }

    /**
     * @param string[] $lines
     */
    abstract protected function addClosingLines(array &$lines, string $enumName): void;
}
