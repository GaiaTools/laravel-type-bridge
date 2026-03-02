---
name: type-bridge-enum-translators
description: Generate TypeScript/JavaScript translator composables for enum-to-translation mapping.
---

# Laravel Type Bridge - Enum Translators

## When to use this skill
Use this skill when you need to:
- Get translated labels for enum values in your frontend
- Create dropdown/select options from translated enums
- Display human-readable enum values with i18n support

## Overview

This package generates per-enum translator composables that map enum values to translated labels using your configured i18n library. Check `config/type-bridge.php` for output paths and discovery settings.

## Available Commands

```bash
# Generate enum translators using default format
php artisan type-bridge:enum-translators

# Force TypeScript format
php artisan type-bridge:enum-translators --format=ts

# Force JavaScript format
php artisan type-bridge:enum-translators --format=js

# Dry-run: discover candidates without writing files
php artisan type-bridge:enum-translators --dry
```

## Setup Prerequisites

### 1. Publish Translator Utilities

Run once to publish required helper files:

```bash
php artisan type-bridge:publish-translator-utils [--force]
```

This publishes:
- `composables/useTranslator.(ts|js)`
- `lib/createEnumTranslationMap.(ts|js)`
- `lib/translators.(ts|js)`

### 2. Configure Translation Engine

In your app entry point (app.ts/main.ts):

#### TypeScript (i18next/react-i18next)
```ts
import i18n from '@/i18n';
import { configureTranslationEngine } from '@/composables/useTranslator';

configureTranslationEngine({
  t: (key: string) => i18n.t(key),
});
```

#### TypeScript (vue-i18n)
```ts
import { createI18n } from 'vue-i18n';
import { configureTranslationEngine } from '@/composables/useTranslator';

const i18nVue = createI18n({ /* ... */ });

configureTranslationEngine({
  t: (key: string) => i18nVue.global.t(key) as string,
});
```

## Using Generated Translators

After generating translators and configuring the engine:

```typescript
import { Status } from '@/enums/generated/Status';
import { createEnumTranslationMap } from '@/lib/createEnumTranslationMap';
import { useTranslator } from '@/composables/useTranslator';

const statusMap = createEnumTranslationMap(Status, 'Status');
const tStatus = useTranslator(statusMap);

// Translate an enum value
tStatus(Status.Active); // → "Active"

// Build select options for dropdowns
const options = tStatus.options();
// → [{ value: 'active', label: 'Active' }, ...]

// Filter to specific values only
const activeOptions = tStatus.options([Status.Active, Status.Pending]);
// → [{ value: 'active', label: 'Active' }, { value: 'pending', label: 'Pending' }]

// Override labels with a record
const customOptions = tStatus.options({ active: 'Enabled', pending: 'Awaiting' });
// → [{ value: 'active', label: 'Enabled' }, { value: 'pending', label: 'Awaiting' }]

// Check if a value has a translation mapping
const hasPending = tStatus.has(Status.Pending); // true/false
```

## Opt-in Enum Translators

Use the `#[GenerateTranslator]` attribute on enums:

```php
<?php

namespace App\Enums;

use GaiaTools\TypeBridge\Attributes\GenerateEnum;
use GaiaTools\TypeBridge\Attributes\GenerateTranslator;

#[GenerateEnum]
#[GenerateTranslator]
enum Status: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Pending = 'pending';
}
```

Or use `hasTranslator` option on `#[GenerateEnum]`:

```php
use GaiaTools\TypeBridge\Attributes\GenerateEnum;

#[GenerateEnum(hasTranslator: true)]
enum Status: string { ... }
```

## Translation Key Format

Translation keys follow this pattern: `{EnumName}.{caseName}`

In your lang files (e.g., `lang/en/status.php`):

```php
<?php

return [
    'active' => 'Active',
    'inactive' => 'Inactive',
    'pending' => 'Pending',
];
```

Or nested:

```php
return [
    'status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],
];
```

## Dry-Run Output

Use `--dry` to see eligible enums before generating:

```
Enum                          Prefix     In FE Set   Has Translations
App\Enums\Status              Status     Yes         Yes
App\Enums\OrderStatus         OrderStatus Yes        No
```

Only enums that are both in the frontend generation set AND have translations are eligible.
