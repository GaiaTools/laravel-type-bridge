<?php

namespace GaiaTools\TypeBridge\Support;

use GaiaTools\TypeBridge\Contracts\FileEnumerator;
use SplFileInfo;
use UnexpectedValueException;

final class EnforcingFileEnumerator implements FileEnumerator
{
    public function __construct(private readonly FileEnumerator $inner) {}

    public function enumerate(string $directory): iterable
    {
        /** @var iterable<mixed> $iter */
        $iter = $this->inner->enumerate($directory);

        foreach ($iter as $file) {
            if (! $file instanceof SplFileInfo) {
                throw new UnexpectedValueException('Non-SplFileInfo: '.get_debug_type($file));
            }

            yield $file;
        }
    }
}
