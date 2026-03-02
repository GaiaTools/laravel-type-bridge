<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\Support;

use GaiaTools\TypeBridge\Support\EnumGroupValueFormatter;
use GaiaTools\TypeBridge\Tests\TestCase;
use GaiaTools\TypeBridge\ValueObjects\EnumGroupValue;
use PHPUnit\Framework\Attributes\Test;

final class EnumGroupValueFormatterTest extends TestCase
{
    #[Test]
    public function it_formats_enum_references(): void
    {
        $value = new EnumGroupValue(EnumGroupValue::KIND_ENUM, 'ALPHA');

        $this->assertSame('Sample.ALPHA', EnumGroupValueFormatter::format($value, 'Sample'));
    }

    #[Test]
    public function it_formats_literal_values(): void
    {
        $trueValue = new EnumGroupValue(EnumGroupValue::KIND_LITERAL, true);
        $nullValue = new EnumGroupValue(EnumGroupValue::KIND_LITERAL, null);
        $floatValue = new EnumGroupValue(EnumGroupValue::KIND_LITERAL, 1.5);

        $this->assertSame('true', EnumGroupValueFormatter::format($trueValue, 'Sample'));
        $this->assertSame('null', EnumGroupValueFormatter::format($nullValue, 'Sample'));
        $this->assertSame('1.5', EnumGroupValueFormatter::format($floatValue, 'Sample'));
    }
}
