<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\Adapters;

use GaiaTools\TypeBridge\Adapters\PassthroughSyntaxAdapter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PassthroughSyntaxAdapterTest extends TestCase
{
    private PassthroughSyntaxAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = new PassthroughSyntaxAdapter;
    }

    #[Test]
    public function it_returns_correct_target_library(): void
    {
        $this->assertSame('passthrough', $this->adapter->getTargetLibrary());
    }

    #[Test]
    public function it_transforms_parameters_to_curly_syntax(): void
    {
        $input = [
            'welcome' => 'Welcome, :name!',
        ];

        $expected = [
            'welcome' => 'Welcome, {name}!',
        ];

        $result = $this->adapter->transform($input);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function it_transforms_multiple_parameters(): void
    {
        $input = [
            'greeting' => 'Hello, :user. You have :count messages.',
        ];

        $expected = [
            'greeting' => 'Hello, {user}. You have {count} messages.',
        ];

        $result = $this->adapter->transform($input);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function it_preserves_simple_pluralization(): void
    {
        $input = [
            'items' => 'item|items',
        ];

        $expected = [
            'items' => 'item|items',
        ];

        $result = $this->adapter->transform($input);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function it_transforms_complex_pluralization(): void
    {
        $input = [
            'apples' => '{0} There are none|{1} There is one|[2,*] There are :count',
        ];

        $expected = [
            'apples' => 'There are none|There is one|There are {count}',
        ];

        $result = $this->adapter->transform($input);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function it_transforms_nested_arrays(): void
    {
        $input = [
            'auth' => [
                'failed' => 'These credentials do not match :attribute.',
                'throttle' => 'Too many attempts. Try again in :seconds seconds.',
            ],
        ];

        $expected = [
            'auth' => [
                'failed' => 'These credentials do not match {attribute}.',
                'throttle' => 'Too many attempts. Try again in {seconds} seconds.',
            ],
        ];

        $result = $this->adapter->transform($input);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function it_transforms_deeply_nested_arrays(): void
    {
        $input = [
            'validation' => [
                'custom' => [
                    'email' => [
                        'required' => 'The :attribute field is required.',
                    ],
                ],
            ],
        ];

        $expected = [
            'validation' => [
                'custom' => [
                    'email' => [
                        'required' => 'The {attribute} field is required.',
                    ],
                ],
            ],
        ];

        $result = $this->adapter->transform($input);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function it_preserves_non_string_values(): void
    {
        $input = [
            'number' => 123,
            'boolean' => true,
            'null' => null,
        ];

        $expected = [
            'number' => 123,
            'boolean' => true,
            'null' => null,
        ];

        $result = $this->adapter->transform($input);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function it_transforms_mixed_content(): void
    {
        $input = [
            'simple' => 'No parameters',
            'with_param' => 'Hello :name',
            'plural' => 'item|items',
            'complex_plural' => '{0} None|{1} One|[2,*] :count',
            'nested' => [
                'deep' => 'The :attribute is :value',
            ],
        ];

        $expected = [
            'simple' => 'No parameters',
            'with_param' => 'Hello {name}',
            'plural' => 'item|items',
            'complex_plural' => 'None|One|{count}',
            'nested' => [
                'deep' => 'The {attribute} is {value}',
            ],
        ];

        $result = $this->adapter->transform($input);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function it_handles_empty_array(): void
    {
        $input = [];
        $expected = [];

        $result = $this->adapter->transform($input);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function it_changes_values_when_transformation_applies(): void
    {
        $input = [
            'message' => 'Hello :name',
        ];

        $expected = [
            'message' => 'Hello {name}',
        ];

        $result = $this->adapter->transform($input);

        $this->assertSame($expected, $result);
    }
}
