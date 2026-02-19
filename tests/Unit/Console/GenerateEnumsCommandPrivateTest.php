<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\Console;

use GaiaTools\TypeBridge\Console\Commands\GenerateEnumsCommand;
use GaiaTools\TypeBridge\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class GenerateEnumsCommandPrivateTest extends TestCase
{
    #[Test]
    public function it_resolves_unknown_format_to_ts(): void
    {
        $command = new GenerateEnumsCommand;

        $resolve = new \ReflectionMethod($command, 'resolveExtension');
        $resolve->setAccessible(true);
        $this->assertSame('ts', $resolve->invoke($command, 'bogus'));
    }

    #[Test]
    public function it_handles_non_decorated_output_when_method_missing(): void
    {
        $command = new GenerateEnumsCommand;

        // Force $output property to an object without isDecorated() to cover the fallback branch
        $refObj = new \ReflectionObject($command);
        $prop = $refObj->getParentClass()->getProperty('output'); // property defined on Illuminate\Console\Command
        $prop->setAccessible(true);
        $prop->setValue($command, new class {});

        $isDecorated = new \ReflectionMethod($command, 'isDecorated');
        $isDecorated->setAccessible(true);

        $this->assertFalse($isDecorated->invoke($command));
    }
}
