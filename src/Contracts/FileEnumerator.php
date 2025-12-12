<?php

namespace GaiaTools\TypeBridge\Contracts;

use SplFileInfo;

interface FileEnumerator
{
    /** @return iterable<SplFileInfo> */
    public function enumerate(string $directory): iterable;
}