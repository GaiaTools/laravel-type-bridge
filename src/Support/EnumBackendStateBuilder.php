<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Support;

use GaiaTools\TypeBridge\Transformers\EnumTransformer;
use GaiaTools\TypeBridge\ValueObjects\EnumGroup;
use GaiaTools\TypeBridge\ValueObjects\TransformedEnum;
use Illuminate\Support\Collection;

final class EnumBackendStateBuilder
{
    /**
     * @param  iterable<\ReflectionEnum>  $reflections
     * @return array<string,array{path:string,cases:array<string,string>,groups:array<string,array{kind:string,entries:array<string,string>}>}>
     */
    public function build(iterable $reflections, EnumTransformer $transformer): array
    {
        $result = [];
        foreach ($reflections as $reflection) {
            $transformed = $transformer->transform($reflection);
            $result[$transformed->name] = $this->buildEntry($transformed);
        }

        return $result;
    }

    /**
     * @return array{path:string,cases:array<string,string>,groups:array<string,array{kind:string,entries:array<string,string>}>}
     */
    private function buildEntry(TransformedEnum $transformed): array
    {
        return [
            'path' => $transformed->outputPath,
            'cases' => $this->buildCaseEntries($transformed->cases),
            'groups' => $this->buildGroupEntries($transformed->groups, $transformed->name),
        ];
    }

    /**
     * @param  Collection<int, \GaiaTools\TypeBridge\ValueObjects\EnumCase>  $cases
     * @return array<string,string>
     */
    private function buildCaseEntries(Collection $cases): array
    {
        $entries = [];
        foreach ($cases as $case) {
            $entries[$case->name] = $this->formatCaseValue($case->value);
        }

        return $entries;
    }

    /**
     * @param  Collection<int, EnumGroup>  $groups
     * @return array<string,array{kind:string,entries:array<string,string>}>
     */
    private function buildGroupEntries(Collection $groups, string $enumName): array
    {
        $entries = [];
        foreach ($groups as $group) {
            $entries[$group->name] = [
                'kind' => $group->kind,
                'entries' => $this->formatGroupEntries($group, $enumName),
            ];
        }

        return $entries;
    }

    /**
     * @return array<string,string>
     */
    private function formatGroupEntries(EnumGroup $group, string $enumName): array
    {
        return $group->kind === EnumGroup::KIND_ARRAY
            ? $this->formatArrayGroupEntries($group, $enumName)
            : $this->formatRecordGroupEntries($group, $enumName);
    }

    /**
     * @return array<string,string>
     */
    private function formatArrayGroupEntries(EnumGroup $group, string $enumName): array
    {
        $entries = [];
        foreach ($group->values as $value) {
            $entries[(string) count($entries)] = EnumGroupValueFormatter::format($value, $enumName);
        }

        return $entries;
    }

    /**
     * @return array<string,string>
     */
    private function formatRecordGroupEntries(EnumGroup $group, string $enumName): array
    {
        $entries = [];
        foreach ($group->values as $key => $value) {
            $entries[(string) $key] = EnumGroupValueFormatter::format($value, $enumName);
        }

        return $entries;
    }

    private function formatCaseValue(string|int $value): string
    {
        return is_string($value) ? StringQuoter::quoteJs($value) : (string) $value;
    }
}
