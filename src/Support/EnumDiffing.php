<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Support;

use GaiaTools\TypeBridge\ValueObjects\EnumGroup;

final class EnumDiffing
{
    /**
     * @return array{added:array<int,string>,removed:array<int,string>}
     */
    public static function diffEntries(array $backendMap, array $frontendMap, string $prefix): array
    {
        $backendKeys = array_keys($backendMap);
        $frontendKeys = array_keys($frontendMap);

        $added = self::diffAdded($backendKeys, $frontendKeys, $backendMap, $prefix);
        $removed = self::diffAdded($frontendKeys, $backendKeys, $frontendMap, $prefix);
        $changed = self::diffChanged($backendKeys, $frontendKeys, $backendMap, $frontendMap, $prefix);

        return [
            'added' => array_merge($added, $changed['added']),
            'removed' => array_merge($removed, $changed['removed']),
        ];
    }

    /**
     * @return array{added:array<int,string>,removed:array<int,string>}
     */
    public static function diffGroups(array $backendGroups, array $frontendGroups): array
    {
        $added = [];
        $removed = [];
        $names = array_unique(array_merge(array_keys($backendGroups), array_keys($frontendGroups)));

        foreach ($names as $name) {
            $result = self::diffGroup($name, $backendGroups[$name] ?? null, $frontendGroups[$name] ?? null);
            $added = array_merge($added, $result['added']);
            $removed = array_merge($removed, $result['removed']);
        }

        return ['added' => $added, 'removed' => $removed];
    }

    /**
     * @return array{added:array<int,string>,removed:array<int,string>}
     */
    private static function diffGroup(string $name, ?array $backend, ?array $frontend): array
    {
        if ($backend === null || $frontend === null) {
            return self::diffMissingGroup($name, $backend, $frontend);
        }

        $kindDiff = self::diffGroupKind($name, $backend['kind'], $frontend['kind']);
        $entryDiff = self::diffGroupEntries($name, $backend['kind'], $backend['entries'], $frontend['entries']);

        return self::mergeDiffs($kindDiff, $entryDiff);
    }

    /**
     * @return array{added:array<int,string>,removed:array<int,string>}
     */
    private static function diffGroupKind(string $name, string $backendKind, string $frontendKind): array
    {
        if ($backendKind === $frontendKind) {
            return ['added' => [], 'removed' => []];
        }

        return [
            'added' => [self::formatGroupKindLine($name, $backendKind)],
            'removed' => [self::formatGroupKindLine($name, $frontendKind)],
        ];
    }

    /**
     * @return array{added:array<int,string>,removed:array<int,string>}
     */
    private static function diffGroupEntries(string $name, string $kind, array $backend, array $frontend): array
    {
        $prefix = self::groupPrefix($name, $kind);

        return self::diffEntries($backend, $frontend, $prefix);
    }

    /**
     * @return array<int,string>
     */
    private static function formatGroupLines(string $name, array $group): array
    {
        $lines = [];
        $prefix = self::groupPrefix($name, $group['kind']);

        foreach ($group['entries'] as $key => $value) {
            $lines[] = self::formatDiffLine($prefix, (string) $key, $value);
        }

        return $lines;
    }

    private static function groupPrefix(string $name, string $kind): string
    {
        return $kind === EnumGroup::KIND_ARRAY ? "{$name}[" : "{$name}.";
    }

    private static function formatGroupKindLine(string $name, string $kind): string
    {
        return "{$name}[@kind]: {$kind}";
    }

    /**
     * @return array<int,string>
     */
    private static function diffAdded(array $backendKeys, array $frontendKeys, array $source, string $prefix): array
    {
        $lines = [];
        foreach (array_diff($backendKeys, $frontendKeys) as $key) {
            $lines[] = self::formatDiffLine($prefix, $key, $source[$key]);
        }

        return $lines;
    }

    /**
     * @return array{added:array<int,string>,removed:array<int,string>}
     */
    private static function diffChanged(
        array $backendKeys,
        array $frontendKeys,
        array $backendMap,
        array $frontendMap,
        string $prefix
    ): array {
        $acc = ['added' => [], 'removed' => []];
        foreach (array_intersect($backendKeys, $frontendKeys) as $key) {
            if ($backendMap[$key] !== $frontendMap[$key]) { $acc['added'][] = self::formatDiffLine($prefix, $key, $backendMap[$key]); $acc['removed'][] = self::formatDiffLine($prefix, $key, $frontendMap[$key]); }
        }
        return $acc;
    }

    /**
     * @return array{added:array<int,string>,removed:array<int,string>}
     */
    private static function diffMissingGroup(string $name, ?array $backend, ?array $frontend): array
    {
        if ($backend === null) {
            return ['added' => [], 'removed' => self::formatGroupLines($name, $frontend)];
        }

        return ['added' => self::formatGroupLines($name, $backend), 'removed' => []];
    }

    /**
     * @return array{added:array<int,string>,removed:array<int,string>}
     */
    private static function mergeDiffs(array $left, array $right): array
    {
        return [
            'added' => array_merge($left['added'], $right['added']),
            'removed' => array_merge($left['removed'], $right['removed']),
        ];
    }

    private static function formatDiffLine(string $prefix, string|int $key, string $value): string
    {
        $label = $prefix === '' ? (string) $key : self::applyPrefix($prefix, (string) $key);

        return $label.': '.$value;
    }

    private static function applyPrefix(string $prefix, string $key): string
    {
        return str_ends_with($prefix, '[') ? $prefix.$key.']' : $prefix.$key;
    }
}
