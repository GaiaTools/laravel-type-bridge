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
        $content = "export default {}"; // does not match expected pattern
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
        if (!File::exists($dir)) {
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
        if (!File::exists($dir)) {
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

}
