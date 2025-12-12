<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\Support;

use GaiaTools\TypeBridge\Contracts\FileEnumerator;
use GaiaTools\TypeBridge\Support\EnforcingFileEnumerator;
use GaiaTools\TypeBridge\Tests\TestCase;
use Mockery;
use SplFileInfo;
use UnexpectedValueException;

final class EnforcingFileEnumeratorTest extends TestCase
{
    public function test_it_yields_files_from_inner_enumerator(): void
    {
        $inner = Mockery::mock(FileEnumerator::class);
        $inner->shouldReceive('enumerate')
            ->once()
            ->with('/tmp')
            ->andReturn([
                new SplFileInfo(__FILE__),
            ]);

        $enforcing = new EnforcingFileEnumerator($inner);

        $items = iterator_to_array($enforcing->enumerate('/tmp'));

        self::assertCount(1, $items);
        self::assertInstanceOf(SplFileInfo::class, $items[0]);
    }

    public function test_it_throws_when_inner_yields_non_splfileinfo(): void
    {
        $inner = Mockery::mock(FileEnumerator::class);
        $inner->shouldReceive('enumerate')
            ->once()
            ->andReturn([new \stdClass()]);

        $enforcing = new EnforcingFileEnumerator($inner);

        $this->expectException(UnexpectedValueException::class);

        // Force iteration to execute the guard line
        foreach ($enforcing->enumerate('/tmp') as $_) {
        }
    }
}
