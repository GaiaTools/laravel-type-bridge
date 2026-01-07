---
title: Adapters & Extending
---

# Adapters & Extending

Laravel Type Bridge is designed to be extensible. You can customize how it handles translations or swap out entire components.

## Translation Syntax Adapters

When generating translation files, the package uses syntax adapters to convert Laravel's `:attribute` placeholders into the format expected by the target frontend library.

**Interface**: `GaiaTools\TypeBridge\Contracts\TranslationSyntaxAdapter`

```php
namespace GaiaTools\TypeBridge\Contracts;

interface TranslationSyntaxAdapter
{
    /**
     * Transform translation values to the target library's syntax.
     */
    public function transform(array $translations): array;

    /**
     * Get the identifier for the target library.
     */
    public function getTargetLibrary(): string;
}
```

::: tip

You can provide a custom adapter by setting `translations.custom_adapter` in your `config/type-bridge.php`.

:::

## Implementing a Custom Adapter

If your frontend uses a library not supported out-of-the-box, you can implement your own adapter:

```php
namespace App\TypeBridge;

use GaiaTools\TypeBridge\Contracts\TranslationSyntaxAdapter;

class MyCustomAdapter implements TranslationSyntaxAdapter
{
    public function transform(array $translations): array
    {
        // Your custom logic to transform translation values
        return $translations;
    }

    public function getTargetLibrary(): string
    {
        return 'my-custom-lib';
    }
}
```

## Extending via Service Container

Since the package uses the Laravel Service Container, you can re-bind internal classes to your own implementations in a Service Provider. For example, to swap the [Discoverer](./discoverers.md) for enums:

```php
$this->app->bind(\GaiaTools\TypeBridge\Discoverers\EnumDiscoverer::class, function ($app) {
    return new \App\TypeBridge\MyCustomEnumDiscoverer(...);
});
```
