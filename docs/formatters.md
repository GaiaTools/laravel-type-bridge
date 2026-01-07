---
title: Formatters
---

# Formatters

Formatters take the transformed Value Object and produce the actual string content that will be written to a file. They also define the target file extension.

**Interface**: `GaiaTools\TypeBridge\Contracts\OutputFormatter`

```php
namespace GaiaTools\TypeBridge\Contracts;

interface OutputFormatter
{
    /**
     * Format transformed data into a string.
     */
    public function format(mixed $transformed): string;

    /**
     * Get the file extension for this formatter (e.g., 'ts', 'js', 'json').
     */
    public function getExtension(): string;
}
```

## How they work

- They receive a structured Value Object.
- They iterate over its data to build a string representation.
- They handle language-specific syntax (e.g., `export const` for JS/TS, or JSON objects).

The generated string is then passed to the [Writer](./writers.md) to be saved to disk.
