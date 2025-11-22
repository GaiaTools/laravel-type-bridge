<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Support;

final class StringQuoter
{
    /**
     * Quote a PHP string as a JavaScript/TypeScript string literal using minimal escapes.
     *
     * Rules:
     * - If the value has neither ' nor ": wrap in single quotes; escape backslashes only.
     * - If it has only apostrophes (') : wrap in double quotes; escape backslashes and double quotes.
     * - If it has only double quotes (") : wrap in single quotes; escape backslashes and apostrophes.
     * - If it has both ' and ": wrap in single quotes; escape backslashes and apostrophes (leave double quotes as-is).
     */
    public static function quoteJs(string $value): string
    {
        $hasSingle = str_contains($value, "'");
        $hasDouble = str_contains($value, '"');

        if (! $hasSingle && ! $hasDouble) {
            $escaped = str_replace('\\', '\\\\', $value);

            return "'{$escaped}'";
        }

        if ($hasSingle && ! $hasDouble) {
            $escaped = str_replace(['\\', '"'], ['\\\\', '\\"'], $value);

            return '"'.$escaped.'"';
        }

        // Has double quotes (only or both): prefer single quotes and escape apostrophes
        $escaped = str_replace(['\\', "'"], ['\\\\', "\\'"], $value);

        return "'{$escaped}'";
    }
}
