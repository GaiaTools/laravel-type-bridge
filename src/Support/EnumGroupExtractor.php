<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Support;

use GaiaTools\TypeBridge\ValueObjects\EnumGroup;
use GaiaTools\TypeBridge\ValueObjects\EnumGroupValue;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionEnum;
use ReflectionMethod;
use ReflectionEnumBackedCase;
use UnitEnum;

final class EnumGroupExtractor
{
    /**
     * @param  array<int,string>  $includeMethods
     * @return Collection<int, EnumGroup>
     */
    public function extract(ReflectionEnum $reflection, array $includeMethods): Collection
    {
        if ($includeMethods === []) {
            return collect();
        }

        return $this->extractGroups($reflection, $includeMethods);
    }

    /**
     * @param  array<int,string>  $includeMethods
     * @return Collection<int, EnumGroup>
     */
    private function extractGroups(ReflectionEnum $reflection, array $includeMethods): Collection
    {
        $index = EnumCaseIndex::fromReflection($reflection);
        $usedNames = [];
        $groups = [];

        foreach ($includeMethods as $methodName) {
            $groups[] = $this->buildGroup($reflection, $index, $methodName, $usedNames);
        }

        return collect($groups);
    }

    private function resolveMethod(ReflectionEnum $reflection, string $methodName): ReflectionMethod
    {
        if (! $reflection->hasMethod($methodName)) {
            throw new \RuntimeException("Enum {$reflection->getName()} missing method {$methodName}.");
        }

        $method = $reflection->getMethod($methodName);
        $this->guardMethod($method, $reflection->getName());

        return $method;
    }

    private function guardMethod(ReflectionMethod $method, string $enumName): void
    {
        if (! $method->isPublic() || ! $method->isStatic()) {
            throw new \RuntimeException("Enum {$enumName} method {$method->getName()} must be public static.");
        }

        if ($method->getNumberOfParameters() > 0) {
            throw new \RuntimeException("Enum {$enumName} method {$method->getName()} must have no parameters.");
        }
    }

    private function resolveGroupName(string $methodName, string $enumName, array $used): string
    {
        $groupName = Str::studly($methodName);

        if ($groupName === $enumName || in_array($groupName, $used, true)) {
            throw new \RuntimeException("Enum {$enumName} method {$methodName} creates a duplicate group name.");
        }

        return $groupName;
    }

    private function buildGroup(
        ReflectionEnum $reflection,
        EnumCaseIndex $index,
        string $methodName,
        array &$usedNames
    ): EnumGroup {
        $method = $this->resolveMethod($reflection, $methodName);
        $groupName = $this->resolveGroupName($methodName, $reflection->getShortName(), $usedNames);
        $values = $this->invokeMethod($method);
        $kind = $this->resolveKind($values);
        $usedNames[] = $groupName;

        return new EnumGroup($groupName, $kind, $this->normalizeValues($values, $index, $kind));
    }

    private function invokeMethod(ReflectionMethod $method): array
    {
        $value = $method->invoke(null);

        if (! is_array($value)) {
            throw new \RuntimeException("Enum method {$method->getName()} must return an array.");
        }

        return $value;
    }

    private function resolveKind(array $values): string
    {
        $keys = array_keys($values);
        $isSequential = $keys === range(0, count($values) - 1);

        return $isSequential ? EnumGroup::KIND_ARRAY : EnumGroup::KIND_RECORD;
    }

    /**
     * @return array<int,EnumGroupValue>|array<string,EnumGroupValue>
     */
    private function normalizeValues(array $values, EnumCaseIndex $index, string $kind): array
    {
        if ($kind === EnumGroup::KIND_ARRAY) {
            return $this->normalizeArrayValues($values, $index);
        }

        return $this->normalizeRecordValues($values, $index);
    }

    /** @return array<int,EnumGroupValue> */
    private function normalizeArrayValues(array $values, EnumCaseIndex $index): array
    {
        $result = [];

        foreach ($values as $value) {
            $result[] = $this->normalizeValue($value, $index);
        }

        return $result;
    }

    /** @return array<string,EnumGroupValue> */
    private function normalizeRecordValues(array $values, EnumCaseIndex $index): array
    {
        $result = [];

        foreach ($values as $key => $value) {
            $result[(string) $key] = $this->normalizeValue($value, $index);
        }

        return $result;
    }

    private function normalizeValue(mixed $value, EnumCaseIndex $index): EnumGroupValue
    {
        $caseName = $this->matchEnumValue($value, $index);
        if ($caseName !== null) {
            return new EnumGroupValue(EnumGroupValue::KIND_ENUM, $caseName);
        }

        $this->guardLiteral($value);

        return new EnumGroupValue(EnumGroupValue::KIND_LITERAL, $value);
    }

    private function matchEnumValue(mixed $value, EnumCaseIndex $index): ?string
    {
        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        return $index->matchCase($value);
    }

    private function guardLiteral(mixed $value): void
    {
        if (! is_scalar($value) && $value !== null) {
            throw new \RuntimeException('Enum group values must be scalar, null, or enum cases.');
        }
    }
}

final class EnumCaseIndex
{
    /** @var array<string,bool> */
    private array $byName;

    /** @var array<string,string> */
    private array $byValue;

    private function __construct(array $byName, array $byValue)
    {
        $this->byName = $byName;
        $this->byValue = $byValue;
    }

    public static function fromReflection(ReflectionEnum $reflection): self
    {
        $byName = [];
        $byValue = [];

        foreach ($reflection->getCases() as $case) {
            if ($case instanceof ReflectionEnumBackedCase) {
                $byValue[self::valueKey($case->getBackingValue())] = $case->getName();
            }
            $byName[$case->getName()] = true;
        }

        return new self($byName, $byValue);
    }

    public function matchCase(mixed $value): ?string
    {
        if (is_string($value) && isset($this->byName[$value])) {
            return $value;
        }

        if (is_string($value) || is_int($value)) {
            $key = self::valueKey($value);

            return $this->byValue[$key] ?? null;
        }

        return null;
    }

    private static function valueKey(string|int $value): string
    {
        return (is_int($value) ? 'i:' : 's:').$value;
    }
}
