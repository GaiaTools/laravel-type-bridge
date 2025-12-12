<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\Discoverers;

use GaiaTools\TypeBridge\Attributes\GenerateEnum;
use GaiaTools\TypeBridge\Config\EnumDiscoveryConfig;
use GaiaTools\TypeBridge\Discoverers\EnumDiscoverer;
use GaiaTools\TypeBridge\Support\EnumTokenParser;
use GaiaTools\TypeBridge\Tests\TestCase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
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

        $discoverer = new EnumDiscoverer($config, new EnumTokenParser());
        $discovered = $discoverer->discover();

        $this->assertInstanceOf(Collection::class, $discovered);
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

        $discoverer = new EnumDiscoverer($config, new EnumTokenParser());
        $discovered = $discoverer->discover();

        // Should only find TestPriority and TestRole (have GenerateEnum attribute)
        $this->assertGreaterThanOrEqual(2, $discovered->count());

        foreach ($discovered as $reflection) {
            $attributes = $reflection->getAttributes(GenerateEnum::class);
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

        $discoverer = new EnumDiscoverer($config, new EnumTokenParser());
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

        $discoverer = new EnumDiscoverer($config, new EnumTokenParser());
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
            excludes: ['TestStatus', 'TestPriority', 'TestRole', 'TestNoComments', 'TestNumeric', 'TestStatusWithTranslator', 'TestColor', 'TestSize', 'TestNoComposable'],
        );

        $discoverer = new EnumDiscoverer($config, new EnumTokenParser());
        $discovered = $discoverer->discover();

        $this->assertCount(0, $discovered);
    }

    #[Test]
    public function it_ignores_enum_declarations_that_are_not_autoloadable(): void
    {
        // Create a directory with a PHP file containing an enum that won't be autoloadable
        $testDir = resource_path('test-output/non-autoloadable-enums');
        File::makeDirectory($testDir, 0755, true);

        try {
            // Create a file with an enum in a namespace that doesn't match autoload rules
            $enumFile = $testDir.'/FakeEnum.php';
            File::put($enumFile, <<<'PHP'
                <?php
                namespace Some\Random\Namespace\That\Does\Not\Exist;
                
                enum NonAutoloadableEnum: string
                {
                    case FOO = 'foo';
                    case BAR = 'bar';
                }
                PHP);

            $config = new EnumDiscoveryConfig(
                paths: [$testDir],
                generateBackedEnums: true,
                excludes: [],
            );

            $discoverer = new EnumDiscoverer($config, new EnumTokenParser());
            $discovered = $discoverer->discover();

            // The enum should be ignored because it's not autoloadable (enum_exists returns false)
            $this->assertCount(0, $discovered);
        } finally {
            // Cleanup
            if (File::exists($testDir)) {
                File::deleteDirectory($testDir);
            }
        }
    }
}
