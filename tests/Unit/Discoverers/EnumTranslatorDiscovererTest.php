<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\Discoverers;

use GaiaTools\TypeBridge\Config\EnumTranslatorDiscoveryConfig;
use GaiaTools\TypeBridge\Discoverers\EnumTranslatorDiscoverer;
use GaiaTools\TypeBridge\Support\EnumTokenParser;
use GaiaTools\TypeBridge\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;


class EnumTranslatorDiscovererTest extends TestCase
{
    #[Test]
    public function it_discovers_enums_with_translator_attribute(): void
    {
        $config = new EnumTranslatorDiscoveryConfig(
            enabled: true,
            discoveryPaths: [__DIR__.'/../../Fixtures/Enums'],
            excludes: [],
            outputPath: 'js/composables/generated',
            utilsComposablesPath: 'js/composables',
            utilsLibPath: 'js/lib'
        );

        $discoverer = new EnumTranslatorDiscoverer($config, new EnumTokenParser());
        $discovered = $discoverer->discover();

        $this->assertGreaterThan(0, $discovered->count());
        
        // Check that TestStatusWithTranslator was discovered
        $found = $discovered->first(function ($item) {
            return str_contains($item['reflection']->name, 'TestStatusWithTranslator');
        });
        
        $this->assertNotNull($found);
    }

    #[Test]
    public function it_respects_excludes(): void
    {
        $config = new EnumTranslatorDiscoveryConfig(
            enabled: true,
            discoveryPaths: [__DIR__.'/../../Fixtures/Enums'],
            excludes: ['TestStatusWithTranslator'],
            outputPath: 'js/composables/generated',
            utilsComposablesPath: 'js/composables',
            utilsLibPath: 'js/lib'
        );

        $discoverer = new EnumTranslatorDiscoverer($config, new EnumTokenParser());
        $discovered = $discoverer->discover();

        // Check that TestStatusWithTranslator was excluded
        $found = $discovered->first(function ($item) {
            return str_contains($item['reflection']->name, 'TestStatusWithTranslator');
        });
        
        $this->assertNull($found);
    }

    #[Test]
    public function it_extracts_translation_key_from_attribute(): void
    {
        $config = new EnumTranslatorDiscoveryConfig(
            enabled: true,
            discoveryPaths: [__DIR__.'/../../Fixtures/Enums'],
            excludes: [],
            outputPath: 'js/composables/generated',
            utilsComposablesPath: 'js/composables',
            utilsLibPath: 'js/lib'
        );

        $discoverer = new EnumTranslatorDiscoverer($config, new EnumTokenParser());
        $discovered = $discoverer->discover();

        // Find TestStatusWithTranslator
        $item = $discovered->first(function ($i) {
            return str_contains($i['reflection']->name, 'TestStatusWithTranslator');
        });

        $this->assertNotNull($item);
        $this->assertSame('enums.status', $item['translationKey']);
    }

    #[Test]
    public function it_handles_empty_discovery_paths(): void
    {
        $config = new EnumTranslatorDiscoveryConfig(
            enabled: true,
            discoveryPaths: [],
            excludes: [],
            outputPath: 'js/composables/generated',
            utilsComposablesPath: 'js/composables',
            utilsLibPath: 'js/lib'
        );

        $discoverer = new EnumTranslatorDiscoverer($config, new EnumTokenParser());
        $discovered = $discoverer->discover();

        $this->assertSame(0, $discovered->count());
    }

    #[Test]
    public function it_ignores_non_enum_classes(): void
    {
        // Create a temporary directory with a non-enum PHP file
        $tempDir = resource_path('test-output/temp-translator-test');
        \Illuminate\Support\Facades\File::ensureDirectoryExists($tempDir);

        try {
            $testFile = $tempDir.'/NotAnEnum.php';
            \Illuminate\Support\Facades\File::put($testFile, <<<'PHP'
<?php
namespace Test;

class NotAnEnum {
    const FOO = 'bar';
}
PHP
            );

            $config = new EnumTranslatorDiscoveryConfig(
                enabled: true,
                discoveryPaths: [$tempDir],
                excludes: [],
                outputPath: 'js/composables/generated',
                utilsComposablesPath: 'js/composables',
                utilsLibPath: 'js/lib'
            );

            $discoverer = new EnumTranslatorDiscoverer($config, new EnumTokenParser());
            $discovered = $discoverer->discover();

            // Should not discover non-enum classes
            $this->assertSame(0, $discovered->count());
        } finally {
            \Illuminate\Support\Facades\File::deleteDirectory($tempDir);
        }
    }

    #[Test]
    public function it_discovers_enums_with_complex_namespace(): void
    {
        // Create a temporary directory with an enum in a complex namespace
        $tempDir = resource_path('test-output/complex-namespace-enum');
        \Illuminate\Support\Facades\File::ensureDirectoryExists($tempDir);

        try {
            $testFile = $tempDir.'/ComplexEnum.php';
            \Illuminate\Support\Facades\File::put($testFile, <<<'PHP'
<?php
namespace Very\Deep\Nested\Namespace\Structure;

use GaiaTools\TypeBridge\Attributes\GenerateTranslator;

#[GenerateTranslator(translationKey: 'complex.enum')]
enum ComplexEnum: string
{
    case OPTION_ONE = 'one';
    case OPTION_TWO = 'two';
}
PHP
            );

            // Need to include the file to make it autoloadable
            require_once $testFile;

            $config = new EnumTranslatorDiscoveryConfig(
                enabled: true,
                discoveryPaths: [$tempDir],
                excludes: [],
                outputPath: 'js/composables/generated',
                utilsComposablesPath: 'js/composables',
                utilsLibPath: 'js/lib'
            );

            $discoverer = new EnumTranslatorDiscoverer($config, new EnumTokenParser());
            $discovered = $discoverer->discover();

            // Should discover the enum with complex namespace
            $this->assertGreaterThan(0, $discovered->count());
            
            $found = $discovered->first(function ($item) {
                return str_contains($item['reflection']->name, 'ComplexEnum');
            });
            
            $this->assertNotNull($found);
            $this->assertSame('complex.enum', $found['translationKey']);
        } finally {
            \Illuminate\Support\Facades\File::deleteDirectory($tempDir);
        }
    }

    #[Test]
    public function it_discovers_multiple_enums_in_single_file(): void
    {
        // Create a temporary directory with multiple enums in one file
        $tempDir = resource_path('test-output/multiple-enums');
        \Illuminate\Support\Facades\File::ensureDirectoryExists($tempDir);

        try {
            $testFile = $tempDir.'/MultipleEnums.php';
            \Illuminate\Support\Facades\File::put($testFile, <<<'PHP'
<?php
namespace Test\Multiple;

use GaiaTools\TypeBridge\Attributes\GenerateTranslator;

#[GenerateTranslator(translationKey: 'first.enum')]
enum FirstEnum: string
{
    case A = 'a';
}

#[GenerateTranslator(translationKey: 'second.enum')]
enum SecondEnum: int
{
    case ONE = 1;
}
PHP
            );

            // Need to include the file to make it autoloadable
            require_once $testFile;

            $config = new EnumTranslatorDiscoveryConfig(
                enabled: true,
                discoveryPaths: [$tempDir],
                excludes: [],
                outputPath: 'js/composables/generated',
                utilsComposablesPath: 'js/composables',
                utilsLibPath: 'js/lib'
            );

            $discoverer = new EnumTranslatorDiscoverer($config, new EnumTokenParser());
            $discovered = $discoverer->discover();

            // Should discover both enums
            $this->assertGreaterThanOrEqual(2, $discovered->count());
            
            $names = $discovered->pluck('reflection')->map(fn($r) => $r->getShortName())->toArray();
            $this->assertContains('FirstEnum', $names);
            $this->assertContains('SecondEnum', $names);
        } finally {
            \Illuminate\Support\Facades\File::deleteDirectory($tempDir);
        }
    }

    #[Test]
    public function it_handles_file_with_no_namespace(): void
    {
        // Create a temporary directory with an enum without namespace
        $tempDir = resource_path('test-output/no-namespace-enum');
        \Illuminate\Support\Facades\File::ensureDirectoryExists($tempDir);

        try {
            $testFile = $tempDir.'/NoNamespaceEnum.php';
            \Illuminate\Support\Facades\File::put($testFile, <<<'PHP'
<?php

use GaiaTools\TypeBridge\Attributes\GenerateTranslator;

#[GenerateTranslator(translationKey: 'no.namespace')]
enum NoNamespaceEnum: string
{
    case VALUE = 'val';
}
PHP
            );

            // Need to include the file to make it autoloadable
            require_once $testFile;

            $config = new EnumTranslatorDiscoveryConfig(
                enabled: true,
                discoveryPaths: [$tempDir],
                excludes: [],
                outputPath: 'js/composables/generated',
                utilsComposablesPath: 'js/composables',
                utilsLibPath: 'js/lib'
            );

            $discoverer = new EnumTranslatorDiscoverer($config, new EnumTokenParser());
            $discovered = $discoverer->discover();

            // Should discover the enum without namespace
            $found = $discovered->first(function ($item) {
                return $item['reflection']->getShortName() === 'NoNamespaceEnum';
            });
            
            $this->assertNotNull($found);
        } finally {
            \Illuminate\Support\Facades\File::deleteDirectory($tempDir);
        }
    }

    #[Test]
    public function it_handles_enum_with_backed_type(): void
    {
        // Create a temporary directory with backed enum
        $tempDir = resource_path('test-output/backed-enum');
        \Illuminate\Support\Facades\File::ensureDirectoryExists($tempDir);

        try {
            $testFile = $tempDir.'/BackedEnum.php';
            \Illuminate\Support\Facades\File::put($testFile, <<<'PHP'
<?php
namespace Test\Backed;

use GaiaTools\TypeBridge\Attributes\GenerateTranslator;

#[GenerateTranslator(translationKey: 'backed.enum')]
enum BackedEnum: int
{
    case FIRST = 100;
    case SECOND = 200;
}
PHP
            );

            // Need to include the file to make it autoloadable
            require_once $testFile;

            $config = new EnumTranslatorDiscoveryConfig(
                enabled: true,
                discoveryPaths: [$tempDir],
                excludes: [],
                outputPath: 'js/composables/generated',
                utilsComposablesPath: 'js/composables',
                utilsLibPath: 'js/lib'
            );

            $discoverer = new EnumTranslatorDiscoverer($config, new EnumTokenParser());
            $discovered = $discoverer->discover();

            // Should discover the backed enum
            $found = $discovered->first(function ($item) {
                return str_contains($item['reflection']->name, 'BackedEnum');
            });
            
            $this->assertNotNull($found);
            $this->assertTrue($found['reflection']->isBacked());
        } finally {
            \Illuminate\Support\Facades\File::deleteDirectory($tempDir);
        }
    }

    #[Test]
    public function it_handles_directory_with_no_php_files(): void
    {
        // Create an empty directory with no PHP files
        $tempDir = resource_path('test-output/empty-dir');
        \Illuminate\Support\Facades\File::ensureDirectoryExists($tempDir);

        try {
            $config = new EnumTranslatorDiscoveryConfig(
                enabled: true,
                discoveryPaths: [$tempDir],
                excludes: [],
                outputPath: 'js/composables/generated',
                utilsComposablesPath: 'js/composables',
                utilsLibPath: 'js/lib'
            );

            $discoverer = new EnumTranslatorDiscoverer($config, new EnumTokenParser());
            
            // Should return empty collection for directory with no PHP files
            $discovered = $discoverer->discover();
            
            $this->assertSame(0, $discovered->count());
        } finally {
            \Illuminate\Support\Facades\File::deleteDirectory($tempDir);
        }
    }

}
