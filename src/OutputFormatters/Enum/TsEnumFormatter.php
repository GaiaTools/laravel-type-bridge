<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\OutputFormatters\Enum;

use GaiaTools\TypeBridge\ValueObjects\EnumGroup;

final class TsEnumFormatter extends AbstractEnumFormatter
{
    /**
     * @param  string[]  $lines
     */
    protected function addClosingLines(array &$lines, string $enumName): void
    {
        $lines[] = '} as const;';
        $lines[] = '';
        $lines[] = sprintf('export type %1$s = typeof %1$s[keyof typeof %1$s];', $enumName);
        $lines[] = '';
    }

    /**
     * @param  string[]  $lines
     */
    protected function addGroupTypeLines(array &$lines, EnumGroup $group): void
    {
        $lines[] = sprintf('export type %1$s = %2$s;', $group->name, $this->groupTypeExpression($group));
        $lines[] = '';
    }

    protected function groupClosing(EnumGroup $group): string
    {
        return $group->kind === EnumGroup::KIND_ARRAY ? '] as const;' : '} as const;';
    }

    private function groupTypeExpression(EnumGroup $group): string
    {
        if ($group->kind === EnumGroup::KIND_ARRAY) {
            return sprintf('typeof %1$s[number]', $group->name);
        }

        return sprintf('typeof %1$s[keyof typeof %1$s]', $group->name);
    }

    public function getExtension(): string
    {
        return 'ts';
    }
}
