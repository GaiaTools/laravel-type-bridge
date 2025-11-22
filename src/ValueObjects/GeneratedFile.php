<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\ValueObjects;

final class GeneratedFile
{
    public const GENERATED_FILE_WARNING = [
        '// !!!!',
        '// This is a generated file.',
        '// Do not manually change this file',
        '// !!!!',
    ];

    public const DEFAULT_MAX_LINE_LENGTH = 120;

    public function __construct(
        public string $path,
        public string $contents,
    ) {}

    /**
     * Create a GeneratedFile from an array of lines.
     *
     * @param  array<int, string>  $lines
     */
    public static function fromLines(string $path, array $lines, ?int $maxLineLength = self::DEFAULT_MAX_LINE_LENGTH): self
    {
        // If max length is disabled (null or <= 0), skip any ESLint directive handling
        if ($maxLineLength !== null && $maxLineLength > 0) {
            $shouldDisable = self::shouldDisableMaxLen($lines, $maxLineLength);
            if ($shouldDisable) {
                array_unshift($lines, '/* eslint-disable max-len */');
            }
        }

        $contents = implode("\n", $lines)."\n";

        return new self($path, $contents);
    }

    /**
     * Check if any line exceeds max length.
     *
     * @param  array<int, string>  $lines
     */
    private static function shouldDisableMaxLen(array $lines, int $maxLineLength): bool
    {
        foreach ($lines as $line) {
            if (strlen($line) > $maxLineLength) {
                return true;
            }
        }

        return false;
    }
}
