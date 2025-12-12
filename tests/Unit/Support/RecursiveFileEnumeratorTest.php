<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\Support;

use GaiaTools\TypeBridge\Support\RecursiveFileEnumerator;
use GaiaTools\TypeBridge\Tests\TestCase;
use SplFileInfo;

final class RecursiveFileEnumeratorTest extends TestCase
{
    public function test_enumerate_returns_empty_when_directory_does_not_exist(): void
    {
        $enumerator = new RecursiveFileEnumerator;

        $missing = sys_get_temp_dir().'/tb_missing_'.uniqid();
        self::assertFalse(is_dir($missing));

        $items = iterator_to_array($enumerator->enumerate($missing));

        self::assertSame([], $items);
    }

    public function test_enumerate_yields_files_recursively(): void
    {
        $enumerator = new RecursiveFileEnumerator;

        $base = sys_get_temp_dir().'/tb_enum_'.uniqid();
        $nested = $base.'/nested';

        mkdir($nested, 0755, true);

        $a = $base.'/a.js';
        $b = $nested.'/b.ts';

        file_put_contents($a, 'x');
        file_put_contents($b, 'y');

        try {
            $items = iterator_to_array($enumerator->enumerate($base));

            self::assertNotEmpty($items);

            foreach ($items as $item) {
                self::assertInstanceOf(SplFileInfo::class, $item);
                self::assertTrue($item->isFile());
            }

            $paths = array_map(
                static fn (SplFileInfo $f): string|false => $f->getRealPath(),
                $items
            );

            self::assertContains(realpath($a), $paths);
            self::assertContains(realpath($b), $paths);
        } finally {
            unlink($a);
            unlink($b);
            rmdir($nested);
            rmdir($base);
        }
    }

    public function test_enumerator_throws_if_inner_yields_non_splfileinfo(): void
    {
        // Inject a factory that yields a stdClass instead of SplFileInfo
        $factory = static function (string $_dir): \Traversable {
            return new \ArrayIterator([new \stdClass]);
        };

        $enumerator = new RecursiveFileEnumerator($factory);

        $this->expectException(\UnexpectedValueException::class);

        foreach ($enumerator->enumerate(sys_get_temp_dir()) as $_) {
            // no-op; just iterate to trigger the type check
        }
    }
}
