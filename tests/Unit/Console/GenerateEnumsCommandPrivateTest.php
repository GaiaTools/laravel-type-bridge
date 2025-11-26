<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\Console;

use GaiaTools\TypeBridge\Console\Commands\GenerateEnumsCommand;
use GaiaTools\TypeBridge\Support\StringQuoter;
use GaiaTools\TypeBridge\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class GenerateEnumsCommandPrivateTest extends TestCase
{
    #[Test]
    public function it_resolves_unknown_format_to_ts_and_formats_values(): void
    {
        $command = new GenerateEnumsCommand;

        $resolve = new \ReflectionMethod($command, 'resolveExtension');
        $resolve->setAccessible(true);
        $this->assertSame('ts', $resolve->invoke($command, 'bogus'));

        $formatValue = new \ReflectionMethod($command, 'formatValue');
        $formatValue->setAccessible(true);

        // numeric branch returns raw stringified number (no quotes)
        $this->assertSame('42', $formatValue->invoke($command, 42));

        // string branch uses JS-quoting rules
        $expected = StringQuoter::quoteJs("don't");
        $this->assertSame($expected, $formatValue->invoke($command, "don't"));
    }

    #[Test]
    public function it_handles_non_decorated_output_when_method_missing(): void
    {
        $command = new GenerateEnumsCommand;

        // Force $output property to an object without isDecorated() to cover the fallback branch
        $refObj = new \ReflectionObject($command);
        $prop = $refObj->getParentClass()->getProperty('output'); // property defined on Illuminate\Console\Command
        $prop->setAccessible(true);
        $prop->setValue($command, new class() {});

        $isDecorated = new \ReflectionMethod($command, 'isDecorated');
        $isDecorated->setAccessible(true);

        $this->assertFalse($isDecorated->invoke($command));
    }
}
