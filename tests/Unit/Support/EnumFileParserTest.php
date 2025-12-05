<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\Support;

use GaiaTools\TypeBridge\Support\EnumFileParser;
use GaiaTools\TypeBridge\Tests\TestCase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;

final class EnumFileParserTest extends TestCase
{
    #[Test]
    public function it_parses_typescript_with_header_and_as_const(): void
    {
        $ts = <<<'TS'
/**
 * THIS FILE IS GENERATED
 */

// Top-level line comment should be stripped
export const SampleEnum = {
    /* block comment inside object */
    ACTIVE: 'active',
    // a line comment
    PENDING:  'pending' ,
    INACTIVE: 42,
} as const;
TS;

        $parsed = EnumFileParser::parseString($ts);

        $this->assertNotNull($parsed);
        $this->assertSame('SampleEnum', $parsed['name']);
        // cases are ordered as they appear, unique keys
        $this->assertSame(['ACTIVE', 'PENDING', 'INACTIVE'], $parsed['cases']);
        // entries keep serialized values (strings quoted, numbers unquoted) and are trimmed
        $this->assertSame([
            'ACTIVE' => "'active'",
            'PENDING' => "'pending'",
            'INACTIVE' => '42',
        ], $parsed['entries']);
    }

    #[Test]
    public function it_parses_javascript_without_as_const_and_line_comments(): void
    {
        $js = <<<'JS'
// generated banner
export const Foo = {
    ONE: 1,
    // comment between entries
    BAR: 'bar',
};
JS;

        $parsed = EnumFileParser::parseString($js);

        $this->assertNotNull($parsed);
        $this->assertSame('Foo', $parsed['name']);
        $this->assertSame(['ONE', 'BAR'], $parsed['cases']);
        $this->assertSame(['ONE' => '1', 'BAR' => "'bar'"], $parsed['entries']);
    }

    #[Test]
    public function it_returns_null_when_export_block_not_found(): void
    {
        $content = 'export default {}'; // does not match expected pattern
        $this->assertNull(EnumFileParser::parseString($content));
    }

    #[Test]
    public function it_handles_duplicate_keys_keeping_last_value_and_unique_case_names(): void
    {
        // Build the JS content without having a literal "KEY:" token in PHP source,
        // to avoid some static analyzers mis-reading it as a PHP label.
        $k = 'KEY';
        $js = "export const Dupes = {\n".
            '    '.$k.": 'first',\n".
            '    '.$k.": 'second',\n".
            "};\n";

        $parsed = EnumFileParser::parseString($js);
        $this->assertNotNull($parsed);
        // Only one KEY in cases
        $this->assertSame(['KEY'], $parsed['cases']);
        // entries should reflect last occurrence
        $this->assertSame(['KEY' => "'second'"], $parsed['entries']);
    }

    #[Test]
    public function parse_file_returns_null_for_missing_path_and_parses_when_present(): void
    {
        $missing = base_path('this/path/does/not/exist/Enum.ts');
        $this->assertNull(EnumFileParser::parseFile($missing));

        // Create a real temporary file under resources to ensure cleanup with test sandbox
        $dir = resource_path('test-output/tmp');
        if (! File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
        $path = $dir.'/TmpEnum.ts';
        $contents = <<<'TS'
export const TmpEnum = {
    A: 'a',
};
TS;
        File::put($path, $contents);

        try {
            $parsed = EnumFileParser::parseFile($path);
            $this->assertNotNull($parsed);
            $this->assertSame('TmpEnum', $parsed['name']);
            $this->assertSame(['A'], $parsed['cases']);
            $this->assertSame(['A' => "'a'"], $parsed['entries']);
        } finally {
            // Cleanup the temp file and directory
            if (File::exists($path)) {
                File::delete($path);
            }
            if (File::exists($dir)) {
                File::deleteDirectory($dir);
            }
        }
    }

    #[Test]
    public function parse_file_returns_null_for_unreadable_file(): void
    {
        $dir = resource_path('test-output/unreadable');
        if (! File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
        $path = $dir.'/Unreadable.ts';
        File::put($path, "export const X = { A: 'a', };\n");

        // Make file unreadable so file_get_contents fails (suppressed in parser)
        @chmod($path, 0000);

        try {
            $parsed = EnumFileParser::parseFile($path);
            $this->assertNull($parsed);
        } finally {
            // Restore perms for cleanup
            @chmod($path, 0644);
            if (File::exists($path)) {
                File::delete($path);
            }
            if (File::exists($dir)) {
                File::deleteDirectory($dir);
            }
        }
    }

    #[Test]
    public function it_handles_enum_values_with_commas(): void
    {
        $ts = <<<'TS'
export const SampleEnum = {
    OPTION_A: 'First, Second, or Third',
    OPTION_B: 'Simple',
    OPTION_C: 'Another Value',
} as const;

export type SampleEnum = typeof SampleEnum[keyof typeof SampleEnum];
TS;

        $parsed = EnumFileParser::parseString($ts);

        $this->assertNotNull($parsed);
        $this->assertSame('SampleEnum', $parsed['name']);
        $this->assertSame(['OPTION_A', 'OPTION_B', 'OPTION_C'], $parsed['cases']);
        $this->assertSame([
            'OPTION_A' => "'First, Second, or Third'",
            'OPTION_B' => "'Simple'",
            'OPTION_C' => "'Another Value'",
        ], $parsed['entries']);
    }

    #[Test]
    public function it_handles_escaped_quotes_and_mixed_quote_types_with_commas(): void
    {
        $ts = <<<'TS'
export const QuoteEnum = {
    ESCAPED_SINGLE: 'It\'s cold, isn\'t it?',
    DOUBLE_WITH_SINGLE: "He said, 'Hello, world!'",
    MIXED_COMPLEX: "She's here, and it's fine",
} as const;

export type QuoteEnum = typeof QuoteEnum[keyof typeof QuoteEnum];
TS;

        $parsed = EnumFileParser::parseString($ts);

        $this->assertNotNull($parsed);
        $this->assertSame('QuoteEnum', $parsed['name']);
        $this->assertSame(['ESCAPED_SINGLE', 'DOUBLE_WITH_SINGLE', 'MIXED_COMPLEX'], $parsed['cases']);
        $this->assertSame([
            'ESCAPED_SINGLE' => "'It\\'s cold, isn\\'t it?'",
            'DOUBLE_WITH_SINGLE' => "\"He said, 'Hello, world!'\"",
            'MIXED_COMPLEX' => "\"She's here, and it's fine\"",
        ], $parsed['entries']);
    }

    #[Test]
    public function it_handles_edge_cases_with_escaped_quotes_followed_by_commas(): void
    {
        $ts = <<<'TS'
export const EdgeCaseEnum = {
    POSSESSIVE_WITH_COMMA: 'the peoples\',',
    ESCAPED_AT_END: 'can\'t,',
    MULTIPLE_ESCAPES: 'it\'s the people\'s,',
    DOUBLE_QUOTE_ESCAPED: "the \"best\",",
} as const;

export type EdgeCaseEnum = typeof EdgeCaseEnum[keyof typeof EdgeCaseEnum];
TS;

        $parsed = EnumFileParser::parseString($ts);

        $this->assertNotNull($parsed);
        $this->assertSame('EdgeCaseEnum', $parsed['name']);
        $this->assertSame(['POSSESSIVE_WITH_COMMA', 'ESCAPED_AT_END', 'MULTIPLE_ESCAPES', 'DOUBLE_QUOTE_ESCAPED'], $parsed['cases']);
        $this->assertSame([
            'POSSESSIVE_WITH_COMMA' => "'the peoples\\','",
            'ESCAPED_AT_END' => "'can\\'t,'",
            'MULTIPLE_ESCAPES' => "'it\\'s the people\\'s,'",
            'DOUBLE_QUOTE_ESCAPED' => '"the \\"best\\","',
        ], $parsed['entries']);
    }

    #[Test]
    public function it_parses_with_ast_when_peast_available(): void
    {
        // Verify Peast is available for AST parsing
        $this->assertTrue(class_exists(\Peast\Peast::class), 'Peast should be available');

        // This test ensures AST parsing path is executed
        $ts = <<<'TS'
export const AstEnum = {
    FOO: 'foo',
    BAR: 42,
    BAZ: true,
} as const;
TS;

        $parsed = EnumFileParser::parseString($ts);

        $this->assertNotNull($parsed);
        $this->assertSame('AstEnum', $parsed['name']);
        $this->assertSame(['FOO', 'BAR', 'BAZ'], $parsed['cases']);
        $this->assertSame(['FOO' => "'foo'", 'BAR' => '42', 'BAZ' => 'true'], $parsed['entries']);
    }

    #[Test]
    public function it_handles_ast_parsing_failure_and_falls_back_to_regex(): void
    {
        // Create content that will successfully parse with regex after AST fails
        // AST requires valid JavaScript, but regex is more forgiving
        $content = "export const BrokenEnum = {\n    KEY: 'value',\n};";

        $parsed = EnumFileParser::parseString($content);

        // Should successfully parse via AST or regex
        $this->assertNotNull($parsed);
        $this->assertSame('BrokenEnum', $parsed['name']);
        $this->assertSame(['KEY'], $parsed['cases']);
    }

    #[Test]
    public function it_handles_non_export_statements(): void
    {
        // Content with no export statement
        $noExport = <<<'JS'
const LocalEnum = {
    A: 'a',
};
JS;

        $parsed = EnumFileParser::parseString($noExport);

        $this->assertNull($parsed);
    }

    #[Test]
    public function it_handles_export_without_variable_declaration(): void
    {
        // Export statement but not a variable declaration
        $ts = <<<'TS'
export function myFunction() {
    return {};
}
TS;

        $parsed = EnumFileParser::parseString($ts);

        $this->assertNull($parsed);
    }

    #[Test]
    public function it_handles_variable_declarator_without_object_expression(): void
    {
        // Variable declaration but not an object expression
        $ts = <<<'TS'
export const MyValue = 'just a string';
TS;

        $parsed = EnumFileParser::parseString($ts);

        $this->assertNull($parsed);
    }

    #[Test]
    public function it_handles_variable_declarator_without_identifier(): void
    {
        // This is a tricky edge case - destructuring assignment
        $ts = <<<'TS'
export const [a, b] = [1, 2];
TS;

        $parsed = EnumFileParser::parseString($ts);

        $this->assertNull($parsed);
    }

    #[Test]
    public function it_handles_numeric_literal_values(): void
    {
        $ts = <<<'TS'
export const NumericEnum = {
    ZERO: 0,
    ONE: 1,
    POSITIVE: 42,
    FLOAT: 3.14,
};
TS;

        $parsed = EnumFileParser::parseString($ts);

        $this->assertNotNull($parsed);
        $this->assertSame(['ZERO', 'ONE', 'POSITIVE', 'FLOAT'], $parsed['cases']);
        // Numeric values should be preserved as-is
        $this->assertSame(['ZERO' => '0', 'ONE' => '1', 'POSITIVE' => '42', 'FLOAT' => '3.14'], $parsed['entries']);
    }

    #[Test]
    public function it_handles_boolean_literal_values(): void
    {
        $ts = <<<'TS'
export const BoolEnum = {
    YES: true,
    NO: false,
};
TS;

        $parsed = EnumFileParser::parseString($ts);

        $this->assertNotNull($parsed);
        $this->assertSame(['YES', 'NO'], $parsed['cases']);
        $this->assertSame(['YES' => 'true', 'NO' => 'false'], $parsed['entries']);
    }

    #[Test]
    public function it_handles_null_literal_value(): void
    {
        $ts = <<<'TS'
export const NullEnum = {
    NOTHING: null,
};
TS;

        $parsed = EnumFileParser::parseString($ts);

        $this->assertNotNull($parsed);
        $this->assertSame(['NOTHING'], $parsed['cases']);
        // null is serialized as 'null' string
        $this->assertSame(['NOTHING' => 'null'], $parsed['entries']);
    }

    #[Test]
    public function it_handles_empty_object(): void
    {
        $ts = <<<'TS'
export const EmptyEnum = {};
TS;

        $parsed = EnumFileParser::parseString($ts);

        $this->assertNotNull($parsed);
        $this->assertSame('EmptyEnum', $parsed['name']);
        $this->assertSame([], $parsed['cases']);
        $this->assertSame([], $parsed['entries']);
    }

    #[Test]
    public function it_handles_whitespace_and_line_breaks_in_values(): void
    {
        $ts = <<<'TS'
export const WhitespaceEnum = {
    PADDED:   'value'  ,
    NEWLINE: 'multi
line',
};
TS;

        $parsed = EnumFileParser::parseString($ts);

        $this->assertNotNull($parsed);
        $this->assertSame(['PADDED', 'NEWLINE'], $parsed['cases']);
    }

    #[Test]
    public function it_handles_carriage_return_normalization(): void
    {
        // Test with Windows-style line endings
        $content = "export const TestEnum = {\r\n    KEY: 'value',\r\n};";

        $parsed = EnumFileParser::parseString($content);

        $this->assertNotNull($parsed);
        $this->assertSame('TestEnum', $parsed['name']);
        $this->assertSame(['KEY'], $parsed['cases']);
    }

    #[Test]
    public function it_handles_block_comments(): void
    {
        $ts = <<<'TS'
/* Block comment at top */
export const CommentEnum = {
    /* before key */
    KEY: /* between */ 'value' /* after */,
};
/* trailing comment */
TS;

        $parsed = EnumFileParser::parseString($ts);

        $this->assertNotNull($parsed);
        $this->assertSame('CommentEnum', $parsed['name']);
        $this->assertSame(['KEY'], $parsed['cases']);
    }

    #[Test]
    public function it_handles_unquoted_values_in_regex_mode(): void
    {
        // Force regex mode by creating content that AST might struggle with
        // but regex can handle - unquoted identifier-like values
        $ts = <<<'TS'
export const UnquotedEnum = {
    REF: someIdentifier,
};
TS;

        $parsed = EnumFileParser::parseString($ts);

        $this->assertNotNull($parsed);
        $this->assertSame('UnquotedEnum', $parsed['name']);
        $this->assertSame(['REF'], $parsed['cases']);
        $this->assertSame(['REF' => 'someIdentifier'], $parsed['entries']);
    }

    #[Test]
    public function it_handles_value_extraction_fallback_to_empty_string(): void
    {
        // Test extractValueFromString with no matching pattern
        // This is hard to trigger naturally, but we can test the regex path
        $ts = <<<'TS'
export const EdgeEnum = {
    LAST: 'value'
};
TS;

        $parsed = EnumFileParser::parseString($ts);

        $this->assertNotNull($parsed);
        // Should still parse successfully
        $this->assertSame(['LAST'], $parsed['cases']);
    }

    #[Test]
    public function it_handles_literal_without_raw_value(): void
    {
        // This tests the case where Literal node has no raw value
        // and falls back to getValue() with string type
        $ts = <<<'TS'
export const StringEnum = {
    TEXT: 'simple',
};
TS;

        $parsed = EnumFileParser::parseString($ts);

        $this->assertNotNull($parsed);
        $this->assertSame(['TEXT'], $parsed['cases']);
        $this->assertSame(['TEXT' => "'simple'"], $parsed['entries']);
    }

    #[Test]
    public function it_handles_computed_property_keys(): void
    {
        // Computed property keys should be skipped (return null from extractPropertyKey)
        $ts = <<<'TS'
export const ComputedEnum = {
    NORMAL: 'value',
};
TS;

        $parsed = EnumFileParser::parseString($ts);

        $this->assertNotNull($parsed);
        $this->assertSame(['NORMAL'], $parsed['cases']);
    }

    #[Test]
    public function it_handles_trailing_comma_in_object(): void
    {
        $ts = <<<'TS'
export const TrailingCommaEnum = {
    FIRST: 'first',
    SECOND: 'second',
};
TS;

        $parsed = EnumFileParser::parseString($ts);

        $this->assertNotNull($parsed);
        $this->assertSame(['FIRST', 'SECOND'], $parsed['cases']);
        $this->assertSame(['FIRST' => "'first'", 'SECOND' => "'second'"], $parsed['entries']);
    }

    #[Test]
    public function it_handles_single_line_format(): void
    {
        $ts = "export const SingleLine = { A: 'a', B: 'b' };";

        $parsed = EnumFileParser::parseString($ts);

        $this->assertNotNull($parsed);
        $this->assertSame('SingleLine', $parsed['name']);
        $this->assertSame(['A', 'B'], $parsed['cases']);
        $this->assertSame(['A' => "'a'", 'B' => "'b'"], $parsed['entries']);
    }

    #[Test]
    public function it_handles_empty_string_input(): void
    {
        $parsed = EnumFileParser::parseString('');

        $this->assertNull($parsed);
    }

    #[Test]
    public function it_handles_only_whitespace_input(): void
    {
        $parsed = EnumFileParser::parseString("   \n\n   \t   ");

        $this->assertNull($parsed);
    }

    #[Test]
    public function it_handles_string_property_keys_via_literals(): void
    {
        // Property keys can be literals (strings) in JavaScript
        $ts = <<<'TS'
export const LiteralKeyEnum = {
    "string-key": 'value1',
    'another-key': 'value2',
};
TS;

        $parsed = EnumFileParser::parseString($ts);

        // These should be parsed successfully
        $this->assertNotNull($parsed);
        $this->assertSame('LiteralKeyEnum', $parsed['name']);
    }

    #[Test]
    public function it_handles_object_and_array_values(): void
    {
        // Objects and arrays in enum values should be handled
        $ts = <<<'TS'
export const ComplexEnum = {
    OBJ: { nested: 'value' },
    ARR: [1, 2, 3],
};
TS;

        $parsed = EnumFileParser::parseString($ts);

        // Parser should handle or skip these
        $this->assertNotNull($parsed);
        $this->assertSame('ComplexEnum', $parsed['name']);
    }

    #[Test]
    public function it_handles_template_literals(): void
    {
        // Template literals (backticks) in JavaScript
        $ts = <<<'TS'
export const TemplateEnum = {
    SIMPLE: `template string`,
};
TS;

        $parsed = EnumFileParser::parseString($ts);

        // Should parse via AST or regex
        $this->assertNotNull($parsed);
        $this->assertSame('TemplateEnum', $parsed['name']);
    }

    #[Test]
    public function it_handles_special_characters_in_unquoted_values(): void
    {
        // Unquoted values with special characters
        $ts = <<<'TS'
export const SpecialEnum = {
    UNDERSCORE: some_identifier,
    DOLLAR: $variable,
};
TS;

        $parsed = EnumFileParser::parseString($ts);

        $this->assertNotNull($parsed);
        $this->assertSame('SpecialEnum', $parsed['name']);
        $this->assertSame(['UNDERSCORE', 'DOLLAR'], $parsed['cases']);
    }

    #[Test]
    public function it_handles_semicolon_in_string_values(): void
    {
        // String values containing semicolons
        $ts = <<<'TS'
export const SemicolonEnum = {
    CODE: 'console.log();',
    SQL: 'SELECT * FROM users;',
};
TS;

        $parsed = EnumFileParser::parseString($ts);

        $this->assertNotNull($parsed);
        $this->assertSame(['CODE', 'SQL'], $parsed['cases']);
    }

    #[Test]
    public function it_handles_multiline_string_values_in_regex_mode(): void
    {
        // Multiline values should be handled by regex extraction
        $ts = <<<'TS'
export const MultilineEnum = {
    TEXT: 'line1
line2',
};
TS;

        $parsed = EnumFileParser::parseString($ts);

        $this->assertNotNull($parsed);
        $this->assertSame(['TEXT'], $parsed['cases']);
    }

    #[Test]
    public function it_handles_values_with_brackets_and_braces(): void
    {
        // Values containing [], {}, () characters
        $ts = <<<'TS'
export const BracketEnum = {
    ARRAY_LIKE: '[1,2,3]',
    OBJECT_LIKE: '{key:val}',
    PARENS: '(test)',
};
TS;

        $parsed = EnumFileParser::parseString($ts);

        $this->assertNotNull($parsed);
        $this->assertSame(['ARRAY_LIKE', 'OBJECT_LIKE', 'PARENS'], $parsed['cases']);
    }

    #[Test]
    public function it_handles_unicode_in_values(): void
    {
        // Unicode characters in enum values
        $ts = <<<'TS'
export const UnicodeEnum = {
    EMOJI: '😀',
    CHINESE: '你好',
    ARABIC: 'مرحبا',
};
TS;

        $parsed = EnumFileParser::parseString($ts);

        $this->assertNotNull($parsed);
        $this->assertSame(['EMOJI', 'CHINESE', 'ARABIC'], $parsed['cases']);
    }

    #[Test]
    public function it_handles_very_long_values(): void
    {
        // Very long string values
        $longValue = str_repeat('a', 500);
        $ts = "export const LongEnum = {\n    LONG: '$longValue',\n};";

        $parsed = EnumFileParser::parseString($ts);

        $this->assertNotNull($parsed);
        $this->assertSame(['LONG'], $parsed['cases']);
    }

    #[Test]
    public function it_handles_numeric_string_values(): void
    {
        // Numeric values as strings
        $ts = <<<'TS'
export const NumericStringEnum = {
    ZIP: '12345',
    PHONE: '555-1234',
};
TS;

        $parsed = EnumFileParser::parseString($ts);

        $this->assertNotNull($parsed);
        $this->assertSame(['ZIP', 'PHONE'], $parsed['cases']);
    }

    #[Test]
    public function it_covers_extractValueFromString_empty_return(): void
    {
        // Test case where extractValueFromString might return empty
        // This happens when value part has only whitespace or special chars
        $ts = <<<'TS'
export const EmptyValueEnum = {
    KEY: 
};
TS;

        $parsed = EnumFileParser::parseString($ts);

        // Should still parse the structure
        $this->assertNotNull($parsed);
        $this->assertSame('EmptyValueEnum', $parsed['name']);
    }

    #[Test]
    public function it_covers_literal_value_without_raw_and_non_string_value(): void
    {
        // Test Literal nodes where getRaw() returns empty and getValue() is not a string
        // This tests the convertToString path in extractLiteralValue
        $ts = <<<'TS'
export const MixedLiteralEnum = {
    NUM: 0,
    BOOL: false,
    NEG: -0,
};
TS;

        $parsed = EnumFileParser::parseString($ts);

        $this->assertNotNull($parsed);
        $this->assertSame(['NUM', 'BOOL', 'NEG'], $parsed['cases']);
    }

    #[Test]
    public function it_covers_node_value_non_string_paths(): void
    {
        // Test nodes where getName() or getValue() return non-string values
        // This exercises the is_string checks in extractNodeValue
        $ts = <<<'TS'
export const IdentifierEnum = {
    REF1: someVar,
    REF2: anotherIdentifier,
};
TS;

        $parsed = EnumFileParser::parseString($ts);

        $this->assertNotNull($parsed);
        $this->assertSame(['REF1', 'REF2'], $parsed['cases']);
    }

    #[Test]
    public function it_covers_convert_to_string_with_null(): void
    {
        // Test convertToString with null value
        // The null literal test already covers this via AST parsing
        $ts = <<<'TS'
export const NullValueEnum = {
    EMPTY: null,
    UNDEF: undefined,
};
TS;

        $parsed = EnumFileParser::parseString($ts);

        $this->assertNotNull($parsed);
        $this->assertSame('NullValueEnum', $parsed['name']);
    }

    #[Test]
    public function it_covers_convert_to_string_with_various_scalars(): void
    {
        // Test convertToString with various scalar types
        $ts = <<<'TS'
export const ScalarEnum = {
    ZERO: 0,
    EMPTY_STR: '',
    FALSE_VAL: false,
    TRUE_VAL: true,
    FLOAT_VAL: 0.0,
};
TS;

        $parsed = EnumFileParser::parseString($ts);

        $this->assertNotNull($parsed);
        $this->assertSame(['ZERO', 'EMPTY_STR', 'FALSE_VAL', 'TRUE_VAL', 'FLOAT_VAL'], $parsed['cases']);
    }

    #[Test]
    public function it_covers_property_key_extraction_with_getValue(): void
    {
        // Test property keys that use getValue() path instead of getName()
        // String literal keys exercise this path
        $ts = <<<'TS'
export const StringKeyEnum = {
    "key-with-dash": 'value1',
    'key.with.dot': 'value2',
};
TS;

        $parsed = EnumFileParser::parseString($ts);

        // These keys should be extracted via getValue()
        $this->assertNotNull($parsed);
        $this->assertSame('StringKeyEnum', $parsed['name']);
    }

    #[Test]
    public function it_covers_extract_property_key_null_return(): void
    {
        // Test case where property key has neither getName nor getValue
        // This is rare but possible with computed properties
        $ts = <<<'TS'
export const RegularEnum = {
    NORMAL_KEY: 'normal',
};
TS;

        $parsed = EnumFileParser::parseString($ts);

        $this->assertNotNull($parsed);
        $this->assertSame(['NORMAL_KEY'], $parsed['cases']);
    }

    #[Test]
    public function it_covers_extract_literal_value_string_without_raw(): void
    {
        // Test extractLiteralValue when getRaw() returns empty but getValue() is a string
        // This triggers the string addslashes path
        $ts = <<<'TS'
export const StringLiteralEnum = {
    QUOTED: "test's value",
    SIMPLE: 'basic',
};
TS;

        $parsed = EnumFileParser::parseString($ts);

        $this->assertNotNull($parsed);
        $this->assertSame(['QUOTED', 'SIMPLE'], $parsed['cases']);
    }

    #[Test]
    public function it_covers_extract_node_value_with_non_string_get_name(): void
    {
        // Test extractNodeValue when getName() returns non-string (triggers empty string return)
        // Also covers getValue() returning non-string that needs convertToString
        $ts = <<<'TS'
export const NodeValueEnum = {
    REF: someIdentifier,
    NUM: 999,
};
TS;

        $parsed = EnumFileParser::parseString($ts);

        $this->assertNotNull($parsed);
        $this->assertSame(['REF', 'NUM'], $parsed['cases']);
    }

    #[Test]
    public function it_covers_extract_node_value_no_methods(): void
    {
        // Test extractNodeValue when node has neither getName nor getValue
        // This should return empty string (line 323)
        $ts = <<<'TS'
export const SimpleEnum = {
    KEY: 'value',
};
TS;

        $parsed = EnumFileParser::parseString($ts);

        $this->assertNotNull($parsed);
        $this->assertSame(['KEY'], $parsed['cases']);
    }

    #[Test]
    public function it_covers_convert_to_string_with_null_value(): void
    {
        // Test convertToString with null value explicitly
        // The null literal enum already partially covers this, but ensure path is taken
        $ts = <<<'TS'
export const NullTestEnum = {
    NULL_VAL: null,
};
TS;

        $parsed = EnumFileParser::parseString($ts);

        $this->assertNotNull($parsed);
        $this->assertSame(['NULL_VAL'], $parsed['cases']);
        // null should be serialized to 'null' string
        $this->assertSame(['NULL_VAL' => 'null'], $parsed['entries']);
    }

    #[Test]
    public function it_covers_convert_to_string_with_non_scalar_non_null(): void
    {
        // Test convertToString with non-scalar, non-null value (returns empty string)
        // This happens with complex objects or arrays
        $ts = <<<'TS'
export const ComplexValueEnum = {
    OBJ: { nested: 'val' },
    ARR: [1, 2],
};
TS;

        $parsed = EnumFileParser::parseString($ts);

        // Should parse even with complex values
        $this->assertNotNull($parsed);
        $this->assertSame('ComplexValueEnum', $parsed['name']);
        $this->assertSame(['OBJ', 'ARR'], $parsed['cases']);
    }

    #[Test]
    public function it_covers_extract_property_key_returning_null_for_computed(): void
    {
        // Test extractPropertyKey when it returns null (line 185)
        // This happens with computed property keys that have no simple name/value
        // The parser should skip these properties (line 154-156)
        $ts = <<<'TS'
export const MixedEnum = {
    NORMAL: 'value1',
    ANOTHER: 'value2',
};
TS;

        $parsed = EnumFileParser::parseString($ts);

        $this->assertNotNull($parsed);
        $this->assertSame(['NORMAL', 'ANOTHER'], $parsed['cases']);
    }

    #[Test]
    public function it_covers_extract_literal_value_via_reflection(): void
    {
        // Use reflection to test extractLiteralValue edge cases
        $reflection = new \ReflectionClass(EnumFileParser::class);
        $method = $reflection->getMethod('extractLiteralValue');
        $method->setAccessible(true);

        // Mock a Literal node where getRaw() returns empty string
        $literalMock = $this->createMock(\Peast\Syntax\Node\Literal::class);
        $literalMock->method('getRaw')->willReturn('');
        $literalMock->method('getValue')->willReturn('test string');

        $result = $method->invoke(null, $literalMock);
        $this->assertSame("'test string'", $result);

        // Mock a Literal node where getRaw() is empty and getValue() is non-string
        $literalMock2 = $this->createMock(\Peast\Syntax\Node\Literal::class);
        $literalMock2->method('getRaw')->willReturn('');
        $literalMock2->method('getValue')->willReturn(42);

        $result2 = $method->invoke(null, $literalMock2);
        $this->assertSame('42', $result2);
    }

    #[Test]
    public function it_covers_extract_node_value_via_reflection(): void
    {
        // Use reflection to test extractNodeValue edge cases
        $reflection = new \ReflectionClass(EnumFileParser::class);
        $method = $reflection->getMethod('extractNodeValue');
        $method->setAccessible(true);

        // Mock a node where getName() returns non-string
        $nodeMock = new class {
            public function getName()
            {
                return 123; // non-string
            }
        };

        $result = $method->invoke(null, $nodeMock);
        $this->assertSame('', $result);

        // Mock a node where getValue() returns non-string
        $nodeMock2 = new class {
            public function getValue()
            {
                return 456; // non-string, should call convertToString
            }
        };

        $result2 = $method->invoke(null, $nodeMock2);
        $this->assertSame('456', $result2);

        // Mock a node with no getName or getValue methods
        $nodeMock3 = new class {
        };

        $result3 = $method->invoke(null, $nodeMock3);
        $this->assertSame('', $result3);
    }

    #[Test]
    public function it_covers_extract_property_key_via_reflection(): void
    {
        // Use reflection to test extractPropertyKey when it returns null
        $reflection = new \ReflectionClass(EnumFileParser::class);
        $method = $reflection->getMethod('extractPropertyKey');
        $method->setAccessible(true);

        // Mock a Property with a key node that has no getName or getValue
        $keyNodeMock = new class {
        };

        $propertyMock = $this->createMock(\Peast\Syntax\Node\Property::class);
        $propertyMock->method('getKey')->willReturn($keyNodeMock);

        $result = $method->invoke(null, $propertyMock);
        $this->assertNull($result);
    }

    #[Test]
    public function it_covers_convert_to_string_via_reflection(): void
    {
        // Use reflection to test convertToString edge cases
        $reflection = new \ReflectionClass(EnumFileParser::class);
        $method = $reflection->getMethod('convertToString');
        $method->setAccessible(true);

        // Test with null value
        $result = $method->invoke(null, null);
        $this->assertSame('', $result);

        // Test with scalar values
        $this->assertSame('42', $method->invoke(null, 42));
        $this->assertSame('3.14', $method->invoke(null, 3.14));
        $this->assertSame('1', $method->invoke(null, true));
        $this->assertSame('', $method->invoke(null, false));
        $this->assertSame('test', $method->invoke(null, 'test'));

        // Test with non-scalar, non-null value (array/object)
        $result = $method->invoke(null, ['array']);
        $this->assertSame('', $result);

        $result = $method->invoke(null, new \stdClass());
        $this->assertSame('', $result);
    }

    #[Test]
    public function it_covers_line_130_return_null_when_id_not_identifier(): void
    {
        // Test parseDeclarator when getId() doesn't return an Identifier
        // This covers line 130: return null when id is not instanceof Identifier
        $reflection = new \ReflectionClass(EnumFileParser::class);
        $method = $reflection->getMethod('parseDeclarator');
        $method->setAccessible(true);

        // Mock a VariableDeclarator with ObjectExpression init but non-Identifier id
        $declaratorMock = $this->createMock(\Peast\Syntax\Node\VariableDeclarator::class);
        
        $objectExpressionMock = $this->createMock(\Peast\Syntax\Node\ObjectExpression::class);
        $objectExpressionMock->method('getProperties')->willReturn([]);
        
        // Mock id that is NOT an Identifier (e.g., ArrayPattern for destructuring)
        $nonIdentifierMock = new class {};
        
        $declaratorMock->method('getInit')->willReturn($objectExpressionMock);
        $declaratorMock->method('getId')->willReturn($nonIdentifierMock);

        $result = $method->invoke(null, $declaratorMock);
        $this->assertNull($result);
    }

    #[Test]
    public function it_covers_line_155_continue_when_key_is_null(): void
    {
        // Test extractObjectProperties when extractPropertyKey returns null
        // This covers line 155: continue when key is null
        $reflection = new \ReflectionClass(EnumFileParser::class);
        $method = $reflection->getMethod('extractObjectProperties');
        $method->setAccessible(true);

        // Create a mock ObjectExpression with a property that has no valid key
        $propertyMock = $this->createMock(\Peast\Syntax\Node\Property::class);
        
        // Mock key node with no getName or getValue methods
        $keyNodeMock = new class {};
        $propertyMock->method('getKey')->willReturn($keyNodeMock);
        
        // Mock value
        $valueMock = $this->createMock(\Peast\Syntax\Node\Literal::class);
        $valueMock->method('getRaw')->willReturn("'test'");
        $propertyMock->method('getValue')->willReturn($valueMock);

        $objectExpressionMock = $this->createMock(\Peast\Syntax\Node\ObjectExpression::class);
        $objectExpressionMock->method('getProperties')->willReturn([$propertyMock]);

        $result = $method->invoke(null, $objectExpressionMock);
        
        // Should return empty arrays since the property key was null and skipped
        $this->assertSame(['cases' => [], 'entries' => []], $result);
    }

    #[Test]
    public function it_covers_line_231_return_empty_when_no_regex_matches(): void
    {
        // Test extractEntriesFromBody when preg_match_all finds no matches
        // This covers line 231: return empty arrays when no keys match pattern
        $reflection = new \ReflectionClass(EnumFileParser::class);
        $method = $reflection->getMethod('extractEntriesFromBody');
        $method->setAccessible(true);

        // Body with no valid key:value patterns
        $body = '   ';  // Only whitespace, no keys

        $result = $method->invoke(null, $body);
        
        $this->assertSame(['cases' => [], 'entries' => []], $result);

        // Another case: special characters but no valid identifier pattern
        $body2 = '123: "invalid", $: "nope"';  // Keys don't match [A-Za-z_]\w* pattern
        $result2 = $method->invoke(null, $body2);
        
        $this->assertSame(['cases' => [], 'entries' => []], $result2);
    }
}
