---
title: Transformers
---

# Transformers

Transformers take a raw discovered item (like a `ReflectionEnum`) and convert it into a structured Value Object (intermediate representation).

**Interface**: `GaiaTools\TypeBridge\Contracts\Transformer`

```php
namespace GaiaTools\TypeBridge\Contracts;

interface Transformer
{
    /**
     * Transform source data into an intermediate representation.
     */
    public function transform(mixed $source): mixed;
}
```

## How they work

The transformer stage is where most of the package's logic resides:

- Resolving output paths based on configuration.
- Parsing attributes (e.g., `#[GenerateEnum]`).
- Extracting data from reflection objects.
- Normalizing keys and values.

The output of a Transformer is typically a specialized Value Object like `TransformedEnum` or `TransformedTranslation`, which is then passed to a [Formatter](./formatters.md).
