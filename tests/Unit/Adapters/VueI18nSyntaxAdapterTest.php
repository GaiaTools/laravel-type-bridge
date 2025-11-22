<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\Adapters;

use GaiaTools\TypeBridge\Adapters\VueI18nSyntaxAdapter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class VueI18nSyntaxAdapterTest extends TestCase
{
    private VueI18nSyntaxAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = new VueI18nSyntaxAdapter;
    }

    #[Test]
    public function it_returns_correct_target_library(): void
    {
        $this->assertSame('vue-i18n', $this->adapter->getTargetLibrary());
    }

    #[Test]
    public function it_transforms_simple_parameter_replacement(): void
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
    public function it_transforms_simple_pluralization(): void
    {
        $input = [
            'items' => 'item|items',
        ];

        $expected = [
            'items' => 'item | items',
        ];

        $result = $this->adapter->transform($input);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function it_transforms_complex_pluralization_with_zero(): void
    {
        $input = [
            'apples' => '{0} There are none|{1} There is one|[2,*] There are :count',
        ];

        $expected = [
            'apples' => 'There are none | There is one | There are {count}',
        ];

        $result = $this->adapter->transform($input);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function it_transforms_complex_pluralization_without_zero(): void
    {
        $input = [
            'messages' => '{1} One message|[2,*] :count messages',
        ];

        $expected = [
            'messages' => 'One message | {count} messages',
        ];

        $result = $this->adapter->transform($input);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function it_removes_laravel_plural_condition_markers(): void
    {
        $input = [
            'items' => '{0} No items|{1} One item|[2,*] :count items',
        ];

        $expected = [
            'items' => 'No items | One item | {count} items',
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
    public function it_transforms_pluralization_in_nested_arrays(): void
    {
        $input = [
            'messages' => [
                'items' => '{0} No items|{1} One item|[2,*] :count items',
            ],
        ];

        $expected = [
            'messages' => [
                'items' => 'No items | One item | {count} items',
            ],
        ];

        $result = $this->adapter->transform($input);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function it_preserves_strings_without_parameters(): void
    {
        $input = [
            'simple' => 'This is a simple message',
            'nested' => [
                'message' => 'Another simple message',
            ],
        ];

        $expected = [
            'simple' => 'This is a simple message',
            'nested' => [
                'message' => 'Another simple message',
            ],
        ];

        $result = $this->adapter->transform($input);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function it_handles_multiple_parameters_in_one_string(): void
    {
        $input = [
            'complex' => 'User :name with ID :id has :count items in :location',
        ];

        $expected = [
            'complex' => 'User {name} with ID {id} has {count} items in {location}',
        ];

        $result = $this->adapter->transform($input);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function it_handles_underscores_in_parameter_names(): void
    {
        $input = [
            'message' => 'The :user_name field is :field_type',
        ];

        $expected = [
            'message' => 'The {user_name} field is {field_type}',
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
    public function it_handles_mixed_content_in_arrays(): void
    {
        $input = [
            'simple' => 'No parameters',
            'with_param' => 'Hello :name',
            'plural' => 'item|items',
            'complex_plural' => '{0} None|{1} One|[2,*] :count',
        ];

        $expected = [
            'simple' => 'No parameters',
            'with_param' => 'Hello {name}',
            'plural' => 'item | items',
            'complex_plural' => 'None | One | {count}',
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
    public function it_handles_parameter_at_start_of_string(): void
    {
        $input = [
            'message' => ':name is required',
        ];

        $expected = [
            'message' => '{name} is required',
        ];

        $result = $this->adapter->transform($input);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function it_handles_parameter_at_end_of_string(): void
    {
        $input = [
            'message' => 'Required: :attribute',
        ];

        $expected = [
            'message' => 'Required: {attribute}',
        ];

        $result = $this->adapter->transform($input);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function it_adds_spaces_around_pipes_in_pluralization(): void
    {
        $input = [
            'tight' => 'one|many',
            'spaced' => 'one | many',
        ];

        $expected = [
            'tight' => 'one | many',
            'spaced' => 'one | many',
        ];

        $result = $this->adapter->transform($input);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function it_handles_three_part_pluralization(): void
    {
        $input = [
            'items' => 'no items|one item|:count items',
        ];

        $expected = [
            'items' => 'no items | one item | {count} items',
        ];

        $result = $this->adapter->transform($input);

        $this->assertSame($expected, $result);
    }
}
