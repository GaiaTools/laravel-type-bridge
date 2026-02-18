<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\Support;

use GaiaTools\TypeBridge\Support\JsObjectSerializer;
use GaiaTools\TypeBridge\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class JsObjectSerializerTest extends TestCase
{
    #[Test]
    public function it_serializes_primitives_correctly(): void
    {
        $out = JsObjectSerializer::serializeObject([
            'i' => 42,
            'f' => 3.14,
            't' => true,
            'b' => false,
            'n' => null,
        ]);

        $expected = "{\n".
            "  \"i\": 42,\n".
            "  \"f\": 3.14,\n".
            "  \"t\": true,\n".
            "  \"b\": false,\n".
            "  \"n\": null\n".
            '}';

        $this->assertSame($expected, $out);
    }

    #[Test]
    public function it_quotes_and_escapes_strings_minimally(): void
    {
        $out = JsObjectSerializer::serializeObject([
            'plain' => 'hello',
            'apostrophe' => "don't",
            'double' => 'say "hi"',
            'both' => 'He said: "don\'t"',
            'backslashes' => 'C:\\Temp',
            'unicode' => 'Žlutý kůň',
        ]);

        $this->assertStringContainsString("\"plain\": 'hello'", $out);
        $this->assertStringContainsString("\"apostrophe\": \"don't\"", $out, 'uses double quotes when value contains apostrophe');
        $this->assertStringContainsString("\"double\": 'say \"hi\"'", $out, 'uses single quotes and leaves double quotes');
        $this->assertStringContainsString("\"both\": 'He said: \"don\\'t\"'", $out, 'single quotes with escaped apostrophe');
        $this->assertStringContainsString("\"backslashes\": 'C:\\\\Temp'", $out, 'backslashes escaped');
        $this->assertStringContainsString("\"unicode\": 'Žlutý kůň'", $out, 'unicode preserved');
    }

    #[Test]
    public function it_serializes_objects_with_indentation_and_key_encoding(): void
    {
        $data = [
            'level1' => [
                'nested' => [
                    'α' => 1,
                    '"num"' => 2,
                    '1' => 3,
                ],
            ],
        ];

        $out = JsObjectSerializer::serializeObject($data);

        $expected = "{\n".
            "  \"level1\": {\n".
            "    \"nested\": {\n".
            "      \"α\": 1,\n".
            "      \"\\\"num\\\"\": 2,\n".
            "      1: 3\n".
            "    }\n".
            "  }\n".
            '}';

        $this->assertSame($expected, $out);
    }

    #[Test]
    public function it_serializes_lists_and_nested_objects_in_arrays(): void
    {
        $data = [
            'empty' => [],
            'list' => [1, 'a', true],
            'nested' => [
                ['id' => 1, 'name' => 'A'],
                ['id' => 2, 'name' => 'B'],
            ],
        ];

        $out = JsObjectSerializer::serializeObject($data);

        // empty list is [] (inline)
        $this->assertStringContainsString('"empty": []', $out);

        // list is pretty-printed multi-line
        $this->assertStringContainsString("\"list\": [\n    1,\n    'a',\n    true\n  ]", $out);

        // nested objects inside array are pretty-printed
        $this->assertStringContainsString("\"nested\": [\n    {\n      \"id\": 1,\n      \"name\": 'A'\n    },\n    {\n      \"id\": 2,\n      \"name\": 'B'\n    }\n  ]", $out);
    }

    #[Test]
    public function it_distinguishes_assoc_vs_list_arrays(): void
    {
        $out = JsObjectSerializer::serializeObject([
            'assoc' => ['a' => 1, 'b' => 2],
            'list' => [1, 2],
            'nested' => ['k' => [1, 2]],
        ]);

        // assoc serialized as object with {}
        $this->assertStringContainsString("\"assoc\": {\n    \"a\": 1,\n    \"b\": 2\n  }", $out);
        // list serialized as []
        $this->assertStringContainsString("\"list\": [\n    1,\n    2\n  ]", $out);
        // multi-level nesting respected
        $this->assertStringContainsString("\"nested\": {\n    \"k\": [\n      1,\n      2\n    ]\n  }", $out);
    }

    #[Test]
    public function it_falls_back_to_json_for_non_array_values(): void
    {
        $obj = new \stdClass;
        $obj->x = 1;
        $obj->y = 'z';

        $out = JsObjectSerializer::serializeObject([
            'obj' => $obj,
        ]);

        // json fallback uses compact JSON without spaces
        $this->assertStringContainsString('"obj": {"x":1,"y":"z"}', $out);
    }

    #[Test]
    public function it_can_emit_unquoted_keys_and_custom_quote_styles(): void
    {
        config()->set('type-bridge.avoid_quotes', true);
        config()->set('type-bridge.quote_style', 'single');

        $out = JsObjectSerializer::serializeObject([
            'simple' => 1,
            'with-dash' => 2,
            'with.dot' => 3,
            'with space' => 4,
            'with$' => 5,
            '_private' => 6,
        ]);

        $this->assertStringContainsString('simple: 1', $out);
        $this->assertStringContainsString("'with-dash': 2", $out);
        $this->assertStringContainsString("'with.dot': 3", $out);
        $this->assertStringContainsString("'with space': 4", $out);
        $this->assertStringContainsString('with$: 5', $out);
        $this->assertStringContainsString('_private: 6', $out);

        config()->set('type-bridge.avoid_quotes', false);
        config()->set('type-bridge.quote_style', 'double');
    }

    #[Test]
    public function it_always_quotes_keys_when_avoid_quotes_is_false(): void
    {
        config()->set('type-bridge.avoid_quotes', false);
        config()->set('type-bridge.quote_style', 'single');

        $out = JsObjectSerializer::serializeObject([
            'simple' => 1,
            'with-dash' => 2,
        ]);

        $this->assertStringContainsString("'simple': 1", $out);
        $this->assertStringContainsString("'with-dash': 2", $out);
    }

    #[Test]
    public function it_defaults_to_double_quotes_for_invalid_or_non_string_style(): void
    {
        config()->set('type-bridge.avoid_quotes', true);
        config()->set('type-bridge.quote_style', 'weird');

        $out = JsObjectSerializer::serializeObject([
            'with.dot' => 1,
        ]);

        $this->assertStringContainsString('"with.dot": 1', $out);

        config()->set('type-bridge.quote_style', ['double']);

        $out = JsObjectSerializer::serializeObject([
            'with-dash' => 2,
        ]);

        $this->assertStringContainsString('"with-dash": 2', $out);
    }

    #[Test]
    public function it_emits_numeric_keys_without_quotes(): void
    {
        config()->set('type-bridge.avoid_quotes', false);
        config()->set('type-bridge.quote_style', 'single');

        $out = JsObjectSerializer::serializeObject([
            1 => 'a',
            2 => 'b',
        ]);

        $this->assertStringContainsString("1: 'a'", $out);
        $this->assertStringContainsString("2: 'b'", $out);
    }
}
