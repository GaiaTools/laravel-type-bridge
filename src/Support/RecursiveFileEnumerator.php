<?php

namespace GaiaTools\TypeBridge\Support;

use FilesystemIterator;
use GaiaTools\TypeBridge\Contracts\FileEnumerator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Traversable;
use UnexpectedValueException;

class RecursiveFileEnumerator implements FileEnumerator
{
    /**
     * @var null|callable(string):Traversable
     */
    private $iteratorFactory;

    /**
     * Allow injecting a custom iterator factory for testing.
     * When null, a RecursiveIteratorIterator over RecursiveDirectoryIterator is used.
     */
    public function __construct(callable $iteratorFactory = null)
    {
        $this->iteratorFactory = $iteratorFactory;
    }

    public function enumerate(string $directory): iterable
    {
        if (! is_dir($directory)) {
            return [];
        }

        $iterator = $this->iteratorFactory
            ? ($this->iteratorFactory)($directory)
            : new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
            );

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo) {
                throw new UnexpectedValueException(
                    'File enumerator yielded a non-SplFileInfo value: ' . get_debug_type($file)
                );
            }

            yield $file;
        }
    }
}