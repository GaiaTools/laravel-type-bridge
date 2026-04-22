<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\Support;

use GaiaTools\TypeBridge\Support\JsObjectSerializer;
use GaiaTools\TypeBridge\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class JsObjectSerializerIndentTest extends TestCase
{
    #[Test]
    public function it_defaults_to_four_space_indentation(): void
    {
        $out = JsObjectSerializer::serializeObject([
            'a' => 1,
            'b' => ['c' => 2],
        ]);

        $expected = "{\n".
            "    \"a\": 1,\n".
            "    \"b\": {\n".
            "        \"c\": 2\n".
            "    }\n".
            '}';

        $this->assertSame($expected, $out);
    }

    #[Test]
    public function it_honors_configured_two_space_indentation(): void
    {
        config()->set('type-bridge.indent_spaces', 2);

        $out = JsObjectSerializer::serializeObject([
            'a' => 1,
            'b' => ['c' => 2],
        ]);

        $expected = "{\n".
            "  \"a\": 1,\n".
            "  \"b\": {\n".
            "    \"c\": 2\n".
            "  }\n".
            '}';

        $this->assertSame($expected, $out);
    }

    #[Test]
    public function it_honors_configured_six_space_indentation(): void
    {
        config()->set('type-bridge.indent_spaces', 6);

        $out = JsObjectSerializer::serializeObject([
            'a' => 1,
        ]);

        $expected = "{\n".
            "      \"a\": 1\n".
            '}';

        $this->assertSame($expected, $out);
    }

    #[Test]
    public function it_applies_configured_indentation_to_lists(): void
    {
        config()->set('type-bridge.indent_spaces', 3);

        $out = JsObjectSerializer::serializeObject([
            'list' => [1, 2],
        ]);

        $this->assertStringContainsString("\"list\": [\n      1,\n      2\n   ]", $out);
    }

    #[Test]
    public function it_falls_back_to_default_when_config_is_invalid(): void
    {
        config()->set('type-bridge.indent_spaces', 'not-a-number');

        $out = JsObjectSerializer::serializeObject(['a' => 1]);

        $expected = "{\n".
            "    \"a\": 1\n".
            '}';

        $this->assertSame($expected, $out);
    }

    #[Test]
    public function it_floors_negative_indent_to_zero(): void
    {
        config()->set('type-bridge.indent_spaces', -5);

        $out = JsObjectSerializer::serializeObject(['a' => 1]);

        $expected = "{\n".
            "\"a\": 1\n".
            '}';

        $this->assertSame($expected, $out);
    }
}
