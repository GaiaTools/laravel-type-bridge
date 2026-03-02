<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\Support;

use GaiaTools\TypeBridge\Support\EnumGroupKeyFormatter;
use GaiaTools\TypeBridge\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class EnumGroupKeyFormatterTest extends TestCase
{
    #[Test]
    public function it_formats_keys_based_on_identifier_rules(): void
    {
        $this->assertSame('ALPHA', EnumGroupKeyFormatter::format('ALPHA'));
        $this->assertSame('1', EnumGroupKeyFormatter::format(1));
        $this->assertSame('"custom-key"', EnumGroupKeyFormatter::format('custom-key'));
    }
}
