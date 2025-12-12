<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\Support;

use GaiaTools\TypeBridge\Support\EnumTokenParser;
use GaiaTools\TypeBridge\Tests\TestCase;
use Illuminate\Support\Facades\File;
use PhpToken;
use PHPUnit\Framework\Attributes\Test;

class EnumTokenParserTest extends TestCase
{
    private EnumTokenParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new EnumTokenParser();
    }

    #[Test]
    public function it_extracts_enum_fqcn_from_file(): void
    {
        $result = $this->parser->extractEnumFqcnsFromFile(__DIR__.'/../../Fixtures/Enums/TestStatusWithTranslator.php');

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertContains('GaiaTools\\TypeBridge\\Tests\\Fixtures\\Enums\\TestStatusWithTranslator', $result);
    }

    #[Test]
    public function it_returns_empty_array_for_nonexistent_file(): void
    {
        $result = $this->parser->extractEnumFqcnsFromFile('/path/that/does/not/exist.php');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function it_parses_single_enum_from_tokens(): void
    {
        $code = '<?php namespace Test\\Example; enum FirstEnum { case A; }';
        $tokens = PhpToken::tokenize($code);

        $result = $this->parser->parseTokensForEnums($tokens);

        $this->assertIsArray($result);
        $this->assertContains('Test\\Example\\FirstEnum', $result);
    }

    #[Test]
    public function it_parses_multiple_enums_from_tokens(): void
    {
        $code = '<?php namespace Test\\Example; enum FirstEnum { case A; } enum SecondEnum: int { case B = 1; }';
        $tokens = PhpToken::tokenize($code);

        $result = $this->parser->parseTokensForEnums($tokens);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertContains('Test\\Example\\FirstEnum', $result);
        $this->assertContains('Test\\Example\\SecondEnum', $result);
    }

    #[Test]
    public function it_parses_enum_without_namespace(): void
    {
        $code = '<?php enum SimpleEnum { case FOO; }';
        $tokens = PhpToken::tokenize($code);

        $result = $this->parser->parseTokensForEnums($tokens);

        $this->assertIsArray($result);
        $this->assertContains('SimpleEnum', $result);
    }

    #[Test]
    public function it_consumes_simple_namespace(): void
    {
        $code = '<?php namespace App\\Models;';
        $tokens = PhpToken::tokenize($code);

        // Find namespace token position
        $nsPos = 0;
        foreach ($tokens as $i => $token) {
            if ($token->id === T_NAMESPACE) {
                $nsPos = $i + 1;
                break;
            }
        }

        [$namespace, $newIndex] = $this->parser->consumeNamespace($tokens, $nsPos, count($tokens));

        $this->assertSame('App\\Models', $namespace);
        $this->assertIsInt($newIndex);
        $this->assertGreaterThan($nsPos, $newIndex);
    }

    #[Test]
    public function it_returns_empty_name_when_tokens_end_after_whitespace(): void
    {
        // Simulate: enum <whitespace> and then end of tokens (no name present)
        $tokens = [
            new class { public $id = T_ENUM; public $text = 'enum'; public function is($id) { return $this->id === $id; } },
            new class { public $id = 0; public $text = "\n\t  "; public function is($id) { return false; } },
            // no further tokens -> loop should exhaust and hit the fallback return
        ];

        [$name, $newIndex] = $this->parser->consumeNameAfterEnum($tokens, 1, count($tokens));

        $this->assertSame('', $name);
        // Index should be equal to count($tokens) after consuming whitespace to the end
        $this->assertSame(count($tokens), $newIndex);
    }

    #[Test]
    public function it_consumes_complex_namespace(): void
    {
        $code = '<?php namespace Very\\Deep\\Nested\\Namespace\\Structure;';
        $tokens = PhpToken::tokenize($code);

        // Find namespace token position
        $nsPos = 0;
        foreach ($tokens as $i => $token) {
            if ($token->id === T_NAMESPACE) {
                $nsPos = $i + 1;
                break;
            }
        }

        [$namespace, $newIndex] = $this->parser->consumeNamespace($tokens, $nsPos, count($tokens));

        $this->assertSame('Very\\Deep\\Nested\\Namespace\\Structure', $namespace);
        $this->assertIsInt($newIndex);
    }

    #[Test]
    public function it_consumes_namespace_with_curly_brace(): void
    {
        $code = '<?php namespace App\\Models { enum MyEnum {} }';
        $tokens = PhpToken::tokenize($code);

        // Find namespace token position
        $nsPos = 0;
        foreach ($tokens as $i => $token) {
            if ($token->id === T_NAMESPACE) {
                $nsPos = $i + 1;
                break;
            }
        }

        [$namespace, $newIndex] = $this->parser->consumeNamespace($tokens, $nsPos, count($tokens));

        $this->assertSame('App\\Models', $namespace);
        $this->assertIsInt($newIndex);
    }

    #[Test]
    public function it_consumes_enum_name_after_enum_keyword(): void
    {
        $code = '<?php enum StatusType: string { case ACTIVE = "active"; }';
        $tokens = PhpToken::tokenize($code);

        // Find enum token position
        $enumPos = 0;
        foreach ($tokens as $i => $token) {
            if ($token->id === T_ENUM) {
                $enumPos = $i + 1;
                break;
            }
        }

        [$name, $newIndex] = $this->parser->consumeNameAfterEnum($tokens, $enumPos, count($tokens));

        $this->assertSame('StatusType', $name);
        $this->assertIsInt($newIndex);
    }

    #[Test]
    public function it_consumes_simple_enum_name(): void
    {
        $code = '<?php enum MyEnum { case FOO; }';
        $tokens = PhpToken::tokenize($code);

        // Find enum token position
        $enumPos = 0;
        foreach ($tokens as $i => $token) {
            if ($token->id === T_ENUM) {
                $enumPos = $i + 1;
                break;
            }
        }

        [$name, $newIndex] = $this->parser->consumeNameAfterEnum($tokens, $enumPos, count($tokens));

        $this->assertSame('MyEnum', $name);
        $this->assertIsInt($newIndex);
    }

    #[Test]
    public function it_returns_empty_string_when_enum_followed_by_opening_brace(): void
    {
        // Create a mock token sequence: enum followed immediately by '{'
        $tokens = [
            new class { public $id = T_ENUM; public $text = 'enum'; public function is($id) { return $this->id === $id; } },
            new class { public $id = 0; public $text = ' '; public function is($id) { return false; } },
            new class { public $id = 0; public $text = '{'; public function is($id) { return false; } },
        ];

        [$name, $newIndex] = $this->parser->consumeNameAfterEnum($tokens, 1, count($tokens));

        $this->assertSame('', $name);
        $this->assertSame(2, $newIndex);
    }

    #[Test]
    public function it_extracts_text_from_php_token(): void
    {
        $code = '<?php namespace Test;';
        $tokens = PhpToken::tokenize($code);

        // Find the namespace token
        $namespaceToken = null;
        foreach ($tokens as $token) {
            if ($token->id === T_NAMESPACE) {
                $namespaceToken = $token;
                break;
            }
        }

        $this->assertNotNull($namespaceToken);

        $text = $this->parser->tokText($namespaceToken);

        $this->assertIsString($text);
        $this->assertSame('namespace', $text);
    }

    #[Test]
    public function it_extracts_text_from_string_token(): void
    {
        $text = $this->parser->tokText(';');

        $this->assertIsString($text);
        $this->assertSame(';', $text);
    }

    #[Test]
    public function it_returns_empty_string_for_unknown_token_type(): void
    {
        $text = $this->parser->tokText(null);

        $this->assertSame('', $text);
    }

    #[Test]
    public function it_extracts_text_from_object_with_text_method(): void
    {
        // Create an object with text() method instead of text property
        $token = new class {
            public function text(): string {
                return 'custom_token';
            }
        };

        $text = $this->parser->tokText($token);

        $this->assertSame('custom_token', $text);
    }

    #[Test]
    public function it_checks_if_token_matches_id(): void
    {
        $code = '<?php namespace Test; enum MyEnum { case FOO; }';
        $tokens = PhpToken::tokenize($code);

        foreach ($tokens as $token) {
            if ($token->id === T_NAMESPACE) {
                $this->assertTrue($this->parser->tokIs($token, T_NAMESPACE));
                $this->assertFalse($this->parser->tokIs($token, T_ENUM));
            }

            if ($token->id === T_ENUM) {
                $this->assertTrue($this->parser->tokIs($token, T_ENUM));
                $this->assertFalse($this->parser->tokIs($token, T_NAMESPACE));
            }
        }
    }

    #[Test]
    public function it_returns_false_for_non_token_object(): void
    {
        $result = $this->parser->tokIs('not a token', T_NAMESPACE);

        $this->assertFalse($result);
    }

    #[Test]
    public function it_returns_false_for_object_without_is_method(): void
    {
        // Create an object without is() method
        $token = new class {
            public $id = T_NAMESPACE;
        };

        $result = $this->parser->tokIs($token, T_NAMESPACE);

        $this->assertFalse($result);
    }

    #[Test]
    public function it_extracts_multiple_enums_from_real_file(): void
    {
        // Create a temporary file with multiple enums
        $tempDir = resource_path('test-output/enum-parser-test');
        File::ensureDirectoryExists($tempDir);

        try {
            $testFile = $tempDir.'/MultipleEnums.php';
            File::put($testFile, <<<'PHP'
<?php
namespace Test\Multiple;

enum FirstEnum: string
{
    case A = 'a';
}

enum SecondEnum: int
{
    case ONE = 1;
}

enum ThirdEnum
{
    case FOO;
}
PHP
            );

            $result = $this->parser->extractEnumFqcnsFromFile($testFile);

            $this->assertIsArray($result);
            $this->assertCount(3, $result);
            $this->assertContains('Test\\Multiple\\FirstEnum', $result);
            $this->assertContains('Test\\Multiple\\SecondEnum', $result);
            $this->assertContains('Test\\Multiple\\ThirdEnum', $result);
        } finally {
            File::deleteDirectory($tempDir);
        }
    }

    #[Test]
    public function it_handles_file_with_no_enums(): void
    {
        // Create a temporary file without enums
        $tempDir = resource_path('test-output/enum-parser-test');
        File::ensureDirectoryExists($tempDir);

        try {
            $testFile = $tempDir.'/NoEnums.php';
            File::put($testFile, <<<'PHP'
<?php
namespace Test;

class NotAnEnum
{
    const FOO = 'bar';
}
PHP
            );

            $result = $this->parser->extractEnumFqcnsFromFile($testFile);

            $this->assertIsArray($result);
            $this->assertEmpty($result);
        } finally {
            File::deleteDirectory($tempDir);
        }
    }

    #[Test]
    public function it_returns_empty_string_when_no_tokens_after_enum(): void
    {
        // Test case where we've reached end of tokens without finding a name
        $tokens = [
            new class { public $id = T_ENUM; public $text = 'enum'; public function is($id) { return $this->id === $id; } },
        ];

        // Start at position 1 (beyond the enum token), with count = 1
        [$name, $newIndex] = $this->parser->consumeNameAfterEnum($tokens, 1, 1);

        $this->assertSame('', $name);
        $this->assertSame(1, $newIndex);
    }

    #[Test]
    public function it_returns_empty_string_when_enum_followed_by_only_symbols(): void
    {
        // Test case where enum is followed by symbols but no T_STRING or T_NAME_QUALIFIED
        // This covers line 117 where the loop exhausts without finding a valid name token
        $tokens = [
            new class { public $id = T_ENUM; public $text = 'enum'; public function is($id) { return $this->id === $id; } },
            new class { public $id = T_WHITESPACE; public $text = ' '; public function is($id) { return $this->id === $id; } },
            new class { public $id = null; public $text = '{'; public function is($id) { return false; } },  // Symbol, not a name token
            new class { public $id = null; public $text = '}'; public function is($id) { return false; } },
        ];

        // Start after the enum token at position 1
        [$name, $newIndex] = $this->parser->consumeNameAfterEnum($tokens, 1, count($tokens));

        $this->assertSame('', $name);
        $this->assertGreaterThan(1, $newIndex);
    }
}
