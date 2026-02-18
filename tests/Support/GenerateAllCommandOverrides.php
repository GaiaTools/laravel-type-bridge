<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Console\Commands;

if (! function_exists(__NAMESPACE__.'\\preg_split')) {
    /**
     * Override preg_split in this namespace to simulate a failure case in tests.
     *
     * @return array<int,string>|false
     */
    function preg_split(string $pattern, string $subject, int $limit = -1, int $flags = 0): array|false
    {
        if ($subject === 'force-preg-split-false') {
            return false;
        }

        return \preg_split($pattern, $subject, $limit, $flags);
    }
}
