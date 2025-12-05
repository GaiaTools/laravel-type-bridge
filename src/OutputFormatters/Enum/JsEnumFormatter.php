<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\OutputFormatters\Enum;

final class JsEnumFormatter extends AbstractEnumFormatter
{
    protected function addClosingLines(array &$lines, string $enumName): void
    {
        $lines[] = '};';
        $lines[] = '';
    }

    public function getExtension(): string
    {
        return 'js';
    }
}
