<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\Attributes;

use GaiaTools\TypeBridge\Attributes\GenerateEnum;
use GaiaTools\TypeBridge\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class GenerateEnumTest extends TestCase
{
    #[Test]
    public function it_can_be_instantiated_with_defaults(): void
    {
        $attr = new GenerateEnum;

        $this->assertFalse($attr->requiresComments);
        $this->assertFalse($attr->hasTranslator);
        $this->assertNull($attr->outputFormat);
        $this->assertSame([], $attr->includeMethods);
    }

    #[Test]
    public function it_can_be_instantiated_with_custom_values(): void
    {
        $attr = new GenerateEnum(
            requiresComments: true,
            hasTranslator: true,
            outputFormat: 'ts',
            includeMethods: ['customerCommodityValues']
        );

        $this->assertTrue($attr->requiresComments);
        $this->assertTrue($attr->hasTranslator);
        $this->assertSame('ts', $attr->outputFormat);
        $this->assertSame(['customerCommodityValues'], $attr->includeMethods);
    }
}
