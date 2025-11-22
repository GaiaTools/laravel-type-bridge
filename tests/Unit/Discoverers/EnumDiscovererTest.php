<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\Discoverers;

use GaiaTools\TypeBridge\Config\EnumDiscoveryConfig;
use GaiaTools\TypeBridge\Discoverers\EnumDiscoverer;
use GaiaTools\TypeBridge\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use ReflectionEnum;

class EnumDiscovererTest extends TestCase
{
    #[Test]
    public function it_discovers_backed_enums_when_enabled(): void
    {
        $config = new EnumDiscoveryConfig(
            paths: [__DIR__.'/../../Fixtures/Enums'],
            generateBackedEnums: true,
            excludes: [],
        );

        $discoverer = new EnumDiscoverer($config);
        $discovered = $discoverer->discover();

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $discovered);
        $this->assertGreaterThanOrEqual(3, $discovered->count()); // TestStatus, TestPriority, TestRole
        $this->assertContainsOnlyInstancesOf(ReflectionEnum::class, $discovered);
    }

    #[Test]
    public function it_only_discovers_attributed_enums_when_backed_disabled(): void
    {
        $config = new EnumDiscoveryConfig(
            paths: [__DIR__.'/../../Fixtures/Enums'],
            generateBackedEnums: false,
            excludes: [],
        );

        $discoverer = new EnumDiscoverer($config);
        $discovered = $discoverer->discover();

        // Should only find TestPriority and TestRole (have GenerateEnum attribute)
        $this->assertGreaterThanOrEqual(2, $discovered->count());

        foreach ($discovered as $reflection) {
            $attributes = $reflection->getAttributes(\GaiaTools\TypeBridge\Attributes\GenerateEnum::class);
            $this->assertNotEmpty($attributes, "Enum {$reflection->getName()} should have GenerateEnum attribute");
        }
    }

    #[Test]
    public function it_excludes_enums_by_short_name(): void
    {
        $config = new EnumDiscoveryConfig(
            paths: [__DIR__.'/../../Fixtures/Enums'],
            generateBackedEnums: true,
            excludes: ['TestStatus'],
        );

        $discoverer = new EnumDiscoverer($config);
        $discovered = $discoverer->discover();

        $names = $discovered->map(fn ($ref) => $ref->getShortName());
        $this->assertNotContains('TestStatus', $names);
    }

    #[Test]
    public function it_excludes_enums_by_fqcn(): void
    {
        $config = new EnumDiscoveryConfig(
            paths: [__DIR__.'/../../Fixtures/Enums'],
            generateBackedEnums: true,
            excludes: ['GaiaTools\\TypeBridge\\Tests\\Fixtures\\Enums\\TestStatus'],
        );

        $discoverer = new EnumDiscoverer($config);
        $discovered = $discoverer->discover();

        $names = $discovered->map(fn ($ref) => $ref->getName());
        $this->assertNotContains('GaiaTools\\TypeBridge\\Tests\\Fixtures\\Enums\\TestStatus', $names);
    }

    #[Test]
    public function it_returns_empty_collection_when_no_enums_found(): void
    {
        $config = new EnumDiscoveryConfig(
            paths: [__DIR__.'/../../Fixtures/Enums'],
            generateBackedEnums: true,
            excludes: ['TestStatus', 'TestPriority', 'TestRole'],
        );

        $discoverer = new EnumDiscoverer($config);
        $discovered = $discoverer->discover();

        $this->assertCount(0, $discovered);
    }
}
