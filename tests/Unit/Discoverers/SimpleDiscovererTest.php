<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\Discoverers;

use GaiaTools\TypeBridge\Discoverers\SimpleDiscoverer;
use GaiaTools\TypeBridge\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class SimpleDiscovererTest extends TestCase
{
    #[Test]
    public function it_wraps_non_array_into_single_item_collection(): void
    {
        $discoverer = new SimpleDiscoverer('single');
        $result = $discoverer->discover();

        $this->assertCount(1, $result);
        $this->assertSame('single', $result->first());
    }

    #[Test]
    public function it_treats_associative_array_as_single_item(): void
    {
        $input = ['key' => 'value', 'other' => 1];
        $discoverer = new SimpleDiscoverer($input);
        $result = $discoverer->discover();

        $this->assertCount(1, $result);
        $this->assertSame($input, $result->first());
    }

    #[Test]
    public function it_preserves_numeric_indexed_array_as_list(): void
    {
        $input = ['a', 'b', 'c'];
        $discoverer = new SimpleDiscoverer($input);
        $result = $discoverer->discover();

        $this->assertCount(3, $result);
        $this->assertSame($input, $result->values()->all());
    }
}
