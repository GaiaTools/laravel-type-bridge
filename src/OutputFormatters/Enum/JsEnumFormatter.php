<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\OutputFormatters\Enum;

use GaiaTools\TypeBridge\ValueObjects\EnumGroup;

final class JsEnumFormatter extends AbstractEnumFormatter
{
    /**
     * @param  string[]  $lines
     */
    protected function addClosingLines(array &$lines, string $enumName): void
    {
        $lines[] = '};';
        $lines[] = '';
    }

    protected function groupClosing(EnumGroup $group): string
    {
        return $group->kind === EnumGroup::KIND_ARRAY ? '];' : '};';
    }

    public function getExtension(): string
    {
        return 'js';
    }
}
