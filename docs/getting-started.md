---
title: Getting Started
---

# Getting Started

Follow these steps to install and start using Laravel Type Bridge in your project.

## Installation

Install the package via Composer:

```bash
composer require gaiatools/laravel-type-bridge
```

## Setup

Publish the configuration file. The "smart" publish command will attempt to detect your project setup (TypeScript vs JavaScript and your i18n library):

```bash
php artisan type-bridge:publish
```

If you prefer standard Laravel publishing:

```bash
php artisan vendor:publish --tag=type-bridge-config
```

## First Use: Enums

### 1. Create a Backed Enum

Create a standard PHP backed enum in `app/Enums`. By default, all backed enums in this directory will be automatically discovered:

```php
<?php

namespace App\Enums;

enum Status: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Pending = 'pending';
}
```

### 2. Generate Frontend Files

Run the generator command:

```bash
php artisan type-bridge:enums
```

This generates `resources/js/enums/generated/Status.ts` (assuming TypeScript default):

```typescript
// !!!!
// This is a generated file.
// Do not manually change this file
// !!!!

export const Status = {
    Active: 'active',
    Inactive: 'inactive',
    Pending: 'pending',
} as const;

export type Status = typeof Status[keyof typeof Status];
```

## First Use: Translations

### 1. Generate Locale Files

If you have Laravel translations in `lang/en`, you can export them for your frontend:

```bash
php artisan type-bridge:translations en
```

This will create `resources/js/lang/generated/en.ts` containing your nested translation keys.

### 2. Setup Translation Utilities

To use the advanced enum translation features, publish the frontend utilities:

```bash
php artisan type-bridge:publish-translator-utils
```

This will publish `useTranslator.ts`, `createEnumTranslationMap.ts`, and `translators.ts` to your configured frontend directories.

Then configure the translation engine in your entry file (e.g., `app.ts`):

```typescript
import { configureTranslationEngine } from '@/composables/useTranslator';
import { createVueI18nTranslator } from '@/lib/translators';
import { createI18n } from 'vue-i18n';

const i18n = createI18n({
    legacy: false,
    locale: 'en',
    messages: { /* ... */ }
});

// Configure the engine to use vue-i18n
configureTranslationEngine(createVueI18nTranslator(i18n));
```

### 3. Generate Enum Translators

After setting up your enums, translations, and utilities, you can generate the translator helpers:

```bash
php artisan type-bridge:enum-translators
```

This command will discover enums that are marked for frontend generation and have matching translations, then create a dedicated helper for each.

::: tip
If you're unsure why an enum isn't being generated, run `php artisan type-bridge:enum-translators --dry` to see the eligibility check results.
:::
