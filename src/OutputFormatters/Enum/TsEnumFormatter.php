<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\OutputFormatters\Enum;

final class TsEnumFormatter extends AbstractEnumFormatter
{
    /**
     * @param string[] $lines
     */
    protected function addClosingLines(array &$lines, string $enumName): void
    {
        $lines[] = '} as const;';
        $lines[] = '';
        $lines[] = sprintf('export type %1$s = typeof %1$s[keyof typeof %1$s];', $enumName);
        $lines[] = '';
    }

    public function getExtension(): string
    {
        return 'ts';
    }
}
