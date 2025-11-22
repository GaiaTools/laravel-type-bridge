<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Writers;

use GaiaTools\TypeBridge\ValueObjects\GeneratedFile;
use Illuminate\Support\Facades\File;

final class GeneratedFileWriter
{
    public function write(GeneratedFile $file): void
    {
        $directory = dirname($file->path);
        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::put($file->path, $file->contents);
    }
}
