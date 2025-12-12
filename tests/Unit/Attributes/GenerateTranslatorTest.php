<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\Attributes;

use GaiaTools\TypeBridge\Attributes\GenerateTranslator;
use GaiaTools\TypeBridge\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class GenerateTranslatorTest extends TestCase
{
    #[Test]
    public function it_can_be_instantiated_with_defaults(): void
    {
        $attr = new GenerateTranslator();

        $this->assertNull($attr->translationKey);
        $this->assertTrue($attr->generateComposable);
    }

    #[Test]
    public function it_can_be_instantiated_with_custom_values(): void
    {
        $attr = new GenerateTranslator(translationKey: 'custom.key', generateComposable: false);

        $this->assertSame('custom.key', $attr->translationKey);
        $this->assertFalse($attr->generateComposable);
    }
}
