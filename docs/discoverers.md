---
title: Discoverers
---

# Discoverers

Discoverers are responsible for finding what needs to be generated. For example, the `EnumDiscoverer` scans your configured paths for PHP enums.

**Interface**: `GaiaTools\TypeBridge\Contracts\Discoverer`

```php
namespace GaiaTools\TypeBridge\Contracts;

use Illuminate\Support\Collection;

interface Discoverer
{
    /**
     * Discover items to be transformed.
     *
     * @return Collection<int, mixed>
     */
    public function discover(): Collection;
}
```

## How they work

1. A Discoverer is instantiated with a configuration object and optional helpers (like a token parser).
2. The `discover()` method is called to scan directories or files.
3. It returns a `Collection` of raw items, such as `ReflectionEnum` objects or file paths.

These items are then passed to a [Transformer](./transformers) in the generation pipeline.
