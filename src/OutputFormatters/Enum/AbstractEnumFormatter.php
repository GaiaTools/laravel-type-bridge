<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\OutputFormatters\Enum;

use GaiaTools\TypeBridge\Contracts\OutputFormatter;
use GaiaTools\TypeBridge\Support\EnumGroupKeyFormatter;
use GaiaTools\TypeBridge\Support\EnumGroupValueFormatter;
use GaiaTools\TypeBridge\Support\StringQuoter;
use GaiaTools\TypeBridge\ValueObjects\EnumGroup;
use GaiaTools\TypeBridge\ValueObjects\TransformedEnum;
use Illuminate\Support\Collection;

abstract class AbstractEnumFormatter implements OutputFormatter
{
    private const INDENT = '    ';

    public function format(mixed $transformed): string
    {
        assert($transformed instanceof TransformedEnum);

        $lines = $this->buildEnumLines($transformed);
        $this->appendGroups($lines, $transformed);

        return implode("\n", $lines);
    }

    /**
     * @param  string[]  $lines
     */
    abstract protected function addClosingLines(array &$lines, string $enumName): void;

    /**
     * @param  string[]  $lines
     */
    protected function addGroupTypeLines(array &$lines, EnumGroup $group): void
    {
        unset($lines, $group);
    }

    abstract protected function groupClosing(EnumGroup $group): string;

    /**
     * @return string[]
     */
    private function buildEnumLines(TransformedEnum $transformed): array
    {
        $lines = [sprintf('export const %s = {', $transformed->name)];
        $this->appendCases($lines, $transformed->cases);
        $this->addClosingLines($lines, $transformed->name);

        return $lines;
    }

    /**
     * @param  string[]  $lines
     * @param  Collection<int, \GaiaTools\TypeBridge\ValueObjects\EnumCase>  $cases
     */
    private function appendCases(array &$lines, Collection $cases): void
    {
        $lastIndex = $cases->count() - 1;
        $trailingComma = $this->trailingComma();

        foreach ($cases as $i => $case) {
            $this->appendDocComment($lines, $case->docComment);
            $lines[] = $this->formatCaseLine($case->name, $case->value, $i === $lastIndex && ! $trailingComma);
        }
    }

    /**
     * @param  string[]  $lines
     */
    private function appendDocComment(array &$lines, ?string $docComment): void
    {
        if ($docComment !== null) {
            $lines[] = self::INDENT.$docComment;
        }
    }

    private function formatCaseLine(string $name, string|int $value, bool $noComma): string
    {
        $formatted = is_string($value) ? StringQuoter::quoteJs($value) : $value;
        $comma = $noComma ? '' : ',';

        return sprintf(self::INDENT.'%s: %s%s', $name, $formatted, $comma);
    }

    /**
     * @param  string[]  $lines
     */
    private function appendGroups(array &$lines, TransformedEnum $transformed): void
    {
        if ($transformed->groups->isEmpty()) {
            return;
        }

        $lines[] = '';

        foreach ($transformed->groups as $group) {
            $this->appendGroup($lines, $transformed->name, $group);
        }
    }

    /**
     * @param  string[]  $lines
     */
    private function appendGroup(array &$lines, string $enumName, EnumGroup $group): void
    {
        $lines[] = sprintf('export const %s = %s', $group->name, $this->groupOpening($group));
        $this->appendGroupValues($lines, $enumName, $group);
        $lines[] = $this->groupClosing($group);
        $lines[] = '';
        $this->addGroupTypeLines($lines, $group);
    }

    private function groupOpening(EnumGroup $group): string
    {
        return $group->kind === EnumGroup::KIND_ARRAY ? '[' : '{';
    }

    /**
     * @param  string[]  $lines
     */
    private function appendGroupValues(array &$lines, string $enumName, EnumGroup $group): void
    {
        if ($group->kind === EnumGroup::KIND_ARRAY) {
            $this->appendArrayGroupValues($lines, $enumName, $group);

            return;
        }

        $this->appendRecordGroupValues($lines, $enumName, $group);
    }

    /**
     * @param  string[]  $lines
     */
    private function appendArrayGroupValues(array &$lines, string $enumName, EnumGroup $group): void
    {
        $trailingComma = $this->trailingComma();
        $lastIndex = count($group->values) - 1;

        foreach ($group->values as $i => $value) {
            $formatted = EnumGroupValueFormatter::format($value, $enumName);
            $lines[] = self::INDENT.$formatted.$this->commaFor($i === $lastIndex && ! $trailingComma);
        }
    }

    /**
     * @param  string[]  $lines
     */
    private function appendRecordGroupValues(array &$lines, string $enumName, EnumGroup $group): void
    {
        $trailingComma = $this->trailingComma();
        $keys = array_keys($group->values);
        $lastIndex = count($keys) - 1;

        foreach ($keys as $i => $key) {
            $formattedKey = EnumGroupKeyFormatter::format($key);
            $formattedValue = EnumGroupValueFormatter::format($group->values[$key], $enumName);
            $lines[] = self::INDENT.$formattedKey.': '.$formattedValue.$this->commaFor($i === $lastIndex && ! $trailingComma);
        }
    }

    private function commaFor(bool $noComma): string
    {
        return $noComma ? '' : ',';
    }

    private function trailingComma(): bool
    {
        return (bool) config('type-bridge.trailing_commas', true);
    }
}
