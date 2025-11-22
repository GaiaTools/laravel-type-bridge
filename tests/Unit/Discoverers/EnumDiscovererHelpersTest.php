<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\Discoverers;

use GaiaTools\TypeBridge\Config\EnumDiscoveryConfig;
use GaiaTools\TypeBridge\Discoverers\EnumDiscoverer;
use GaiaTools\TypeBridge\Tests\TestCase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;

class EnumDiscovererHelpersTest extends TestCase
{
    private function makeDiscoverer(array $paths): EnumDiscoverer
    {
        $config = new EnumDiscoveryConfig(paths: $paths, generateBackedEnums: true, excludes: []);

        return new EnumDiscoverer($config);
    }

    #[Test]
    public function discover_handles_empty_and_duplicate_paths_and_ignores_non_enum_php_files(): void
    {
        // Use autoloadable fixtures path so that enum_exists() works
        $fixturesPath = __DIR__.'/../../Fixtures/Enums';

        // Create a temporary non-enum PHP file inside fixtures to ensure it's ignored
        $nonEnum = $fixturesPath.'/TmpNonEnum.php';
        File::put($nonEnum, '<?php\\nnamespace GaiaTools\\\\TypeBridge\\\\Tests\\\\Fixtures\\\\Enums;\\nclass TmpNonEnum {}\\n');

        // Also create an empty dir to include as an extra path
        $emptyDir = resource_path('test-output/empty-src');
        File::makeDirectory($emptyDir, 0755, true);

        try {
            $discoverer = $this->makeDiscoverer([$fixturesPath, $fixturesPath, $emptyDir]);
            $result = $discoverer->discover();

            $this->assertInstanceOf(Collection::class, $result);
            // Our fixtures contain 3 enums; non-enum file must be ignored
            $this->assertGreaterThanOrEqual(3, $result->count());
            $shorts = $result->map(fn ($r) => $r->getShortName())->all();
            $this->assertContains('TestStatus', $shorts);
            $this->assertContains('TestPriority', $shorts);
            $this->assertContains('TestRole', $shorts);
        } finally {
            // cleanup the temporary non-enum file
            if (File::exists($nonEnum)) {
                File::delete($nonEnum);
            }
        }
    }

    #[Test]
    public function discover_on_empty_directory_returns_empty(): void
    {
        $emptyDir = resource_path('test-output/empty-discovery');
        File::makeDirectory($emptyDir, 0755, true);

        $discoverer = $this->makeDiscoverer([$emptyDir]);
        $result = $discoverer->discover();

        $this->assertCount(0, $result);
    }

    #[Test]
    public function extract_enum_fqcns_from_file_returns_empty_when_get_contents_fails(): void
    {
        $discoverer = $this->makeDiscoverer([]);

        $ref = new \ReflectionClass($discoverer);
        $method = $ref->getMethod('extractEnumFqcnsFromFile');
        $method->setAccessible(true);

        // Pass a bogus file path; @file_get_contents returns false
        $out = $method->invoke($discoverer, resource_path('test-output/nope/unknown.php'));
        $this->assertSame([], $out);
    }

    #[Test]
    public function parse_tokens_for_enums_supports_namespace_semicolon_and_brace_and_multiple_and_unique(): void
    {
        $baseDir = resource_path('test-output/tokens');
        File::makeDirectory($baseDir, 0755, true);

        // Semicolon-style namespace, two enums with duplicate names to test uniqueness
        $f1 = $baseDir.'/Semicolon.php';
        File::put($f1, <<<'PHP'
            <?php
            namespace Foo\Bar; 
            enum A {}
            enum A {}
            enum B {}
            PHP);

        // Brace-style namespace with multiple enums
        $f2 = $baseDir.'/Brace.php';
        File::put($f2, <<<'PHP'
            <?php
            namespace Zoo\Quux { 
                enum C {}
                enum D {}
            }
            PHP);

        $discoverer = $this->makeDiscoverer([$baseDir]);

        $ref = new \ReflectionClass($discoverer);
        $extract = $ref->getMethod('extractEnumFqcnsFromFile');
        $extract->setAccessible(true);

        $out1 = $extract->invoke($discoverer, $f1);
        sort($out1);
        $this->assertSame(['Foo\\Bar\\A', 'Foo\\Bar\\B'], $out1, 'semicolon ns and uniqueness');

        $out2 = $extract->invoke($discoverer, $f2);
        sort($out2);
        $this->assertSame(['Zoo\\Quux\\C', 'Zoo\\Quux\\D'], $out2, 'brace ns with multiple');
    }

    #[Test]
    public function consume_name_after_enum_early_exit_and_normal_capture(): void
    {
        $discoverer = $this->makeDiscoverer([]);
        $ref = new \ReflectionClass($discoverer);

        $consume = $ref->getMethod('consumeNameAfterEnum');
        $consume->setAccessible(true);

        // Helper to create token-like objects
        $makeTok = function (string $text) {
            return new class($text)
            {
                public function __construct(public string $text) {}

                public function text(): string
                {
                    return $this->text;
                }
            };
        };

        // Early exit when next token is '{'
        $tokens = [$makeTok('{')];
        [$name] = $consume->invoke($discoverer, $tokens, 0, count($tokens));
        $this->assertSame('', $name);

        // Early exit when next token is '('
        $tokens = [$makeTok('(')];
        [$name] = $consume->invoke($discoverer, $tokens, 0, count($tokens));
        $this->assertSame('', $name);

        // Normal capture skipping whitespace
        $tokens = [$makeTok(' '), $makeTok('TheName'), $makeTok('{')];
        [$name, $next] = $consume->invoke($discoverer, $tokens, 0, count($tokens));
        $this->assertSame('TheName', $name);
        $this->assertSame(2, $next); // cursor advanced past the name

        // Exhausted tokens with only whitespace should hit final return ['', $i]
        $tokens = [$makeTok(' '), $makeTok("\n\t "), $makeTok('   ')];
        [$name, $next] = $consume->invoke($discoverer, $tokens, 0, count($tokens));
        $this->assertSame('', $name);
        $this->assertSame(3, $next);
    }

    #[Test]
    public function tok_text_branches_and_tok_is_false_branch(): void
    {
        $discoverer = $this->makeDiscoverer([]);
        $ref = new \ReflectionClass($discoverer);

        $tokText = $ref->getMethod('tokText');
        $tokText->setAccessible(true);
        $tokIs = $ref->getMethod('tokIs');
        $tokIs->setAccessible(true);

        // 1) object with text property
        $objWithProp = new class
        {
            public string $text = 'via-prop';
        };
        $this->assertSame('via-prop', $tokText->invoke($discoverer, $objWithProp));

        // 2) object with text() method
        $objWithMethod = new class
        {
            public function text(): string
            {
                return 'via-method';
            }
        };
        $this->assertSame('via-method', $tokText->invoke($discoverer, $objWithMethod));

        // 3) plain string
        $this->assertSame('hello', $tokText->invoke($discoverer, 'hello'));

        // 4) unsupported object → empty string fallback
        $unsupported = new class {};
        $this->assertSame('', $tokText->invoke($discoverer, $unsupported));

        // tokIs false branch: object without is() method
        $this->assertFalse($tokIs->invoke($discoverer, $unsupported, T_ENUM));
    }

    #[Test]
    public function is_enum_keyword_token_and_tok_is_true_branch(): void
    {
        $discoverer = $this->makeDiscoverer([]);

        $ref = new \ReflectionClass($discoverer);
        $isEnum = $ref->getMethod('isEnumKeywordToken');
        $isEnum->setAccessible(true);
        $tokIs = $ref->getMethod('tokIs');
        $tokIs->setAccessible(true);

        $enumToken = new class
        {
            public function is(int $id): bool
            {
                return $id === T_ENUM;
            }

            public function text(): string
            {
                return 'enum';
            }
        };

        $this->assertTrue($tokIs->invoke($discoverer, $enumToken, T_ENUM));
        $this->assertTrue($isEnum->invoke($discoverer, $enumToken));
    }

    #[Test]
    public function consume_namespace_direct_invocation_covers_semicolon_and_brace(): void
    {
        $discoverer = $this->makeDiscoverer([]);
        $ref = new \ReflectionClass($discoverer);
        $consumeNs = $ref->getMethod('consumeNamespace');
        $consumeNs->setAccessible(true);

        $makeTok = function (string $text) {
            return new class($text)
            {
                public function __construct(public string $text) {}

                public function text(): string
                {
                    return $this->text;
                }
            };
        };

        // Semicolon style
        $tokens = [$makeTok(' '), $makeTok('Foo'), $makeTok('\\'), $makeTok('Bar'), $makeTok(';')];
        [$ns1, $next1] = $consumeNs->invoke($discoverer, $tokens, 0, count($tokens));
        $this->assertSame('Foo\\Bar', $ns1);
        $this->assertSame(5, $next1);

        // Brace style
        $tokens = [$makeTok('Foo'), $makeTok('\\'), $makeTok('Baz'), $makeTok('{')];
        [$ns2, $next2] = $consumeNs->invoke($discoverer, $tokens, 0, count($tokens));
        $this->assertSame('Foo\\Baz', $ns2);
        $this->assertSame(4, $next2);
    }

    #[Test]
    public function should_include_direct_invocation_respects_excludes(): void
    {
        $enumFqcn = \GaiaTools\TypeBridge\Tests\Fixtures\Enums\TestStatus::class;
        $config = new \GaiaTools\TypeBridge\Config\EnumDiscoveryConfig(paths: [], generateBackedEnums: true, excludes: ['TestStatus', $enumFqcn]);
        $discoverer = new EnumDiscoverer($config);

        $ref = new \ReflectionClass($discoverer);
        $method = $ref->getMethod('shouldInclude');
        $method->setAccessible(true);

        $re = new \ReflectionEnum($enumFqcn);
        $this->assertFalse($method->invoke($discoverer, $re));
    }

    #[Test]
    public function parse_tokens_for_enums_ignores_enum_without_name_after_keyword(): void
    {
        $baseDir = resource_path('test-output/tokens2');
        File::makeDirectory($baseDir, 0755, true);

        $f = $baseDir.'/InvalidEnum.php';
        // enum followed directly by '{' should be ignored (no name captured)
        File::put($f, <<<'PHP'
            <?php
            namespace Foo; 
            enum { }
            enum (Foo) {}
            PHP);

        $discoverer = $this->makeDiscoverer([]);
        $ref = new \ReflectionClass($discoverer);
        $extract = $ref->getMethod('extractEnumFqcnsFromFile');
        $extract->setAccessible(true);

        $out = $extract->invoke($discoverer, $f);
        $this->assertSame([], $out);
    }
}
