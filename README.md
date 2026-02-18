# Gaia Tools Laravel Type Bridge

A Laravel package for generating TypeScript/JavaScript enums and frontend translation files from your Laravel app.


[![Release][release-shield]][release-url]
[![Quality Gate][quality-gate-shield]][sonar-url]
[![License][license-shield]][license-url]
[![Downloads][downloads-shield]][packagist-url]
![Coverage][coverage-shield]

<!-- Badge URLs -->
[release-shield]: https://img.shields.io/packagist/v/GaiaTools/laravel-type-bridge?sort=semver&color=blue
[quality-gate-shield]: https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fsonar.r2websolutions.com%2Fapi%2Fmeasures%2Fcomponent%3Fcomponent%3DGaiaTools_laravel-type-bridge_65a0cd71-382c-4322-a628-a979f8beb401%26metricKeys%3Dalert_status&query=$.component.measures[0].value&label=Quality%20Gate&labelColor=black&color=%23009900
[license-shield]: https://img.shields.io/packagist/l/GaiaTools/laravel-type-bridge?label=License&labelColor=black&color=%23009900
[downloads-shield]: https://img.shields.io/packagist/dt/GaiaTools/laravel-type-bridge.svg?label=Downloads&labelColor=black&color=%23009900
[coverage-shield]: https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fsonar.r2websolutions.com%2Fapi%2Fmeasures%2Fcomponent%3Fcomponent%3DGaiaTools_laravel-type-bridge_65a0cd71-382c-4322-a628-a979f8beb401%26metricKeys%3Dcoverage&query=$.component.measures[0].value&suffix=%25&label=Coverage&labelColor=black

<!-- Link URLs -->
[release-url]: https://github.com/GaiaTools/laravel-type-bridge/releases
[sonar-url]: https://sonar.r2websolutions.com/dashboard?id=GaiaTools_laravel-type-bridge_65a0cd71-382c-4322-a628-a979f8beb401
[license-url]: https://github.com/GaiaTools/laravel-type-bridge/blob/main/LICENSE
[packagist-url]: https://packagist.org/packages/GaiaTools/laravel-type-bridge


## Features

- Automatically discover and generate frontend enums from PHP backed enums
- Opt-in enum generation using the `#[GenerateEnum]` attribute
- Flexible discovery via configurable paths (supports Modules or custom structures)
- Generate TypeScript or JavaScript output
- Support for translation file generation (TS, JS, or JSON)
- Configurable backed-enum discovery toggle and excludes

## Requirements

- PHP 8.2 or higher
- Laravel 11.x orhigher

## Installation

Install the package via Composer:

```bash
composer require gaiatools/laravel-type-bridge
```

Publish the configuration file:

Smart config publishing
```bash
php artisan type-bridge:publish
```

Or Laravel config publishing
```bash
php artisan vendor:publish --tag=type-bridge-config
```

## Configuration

The configuration file `config/type-bridge.php` provides the following options (env variables in parentheses):

```php
return [
    // Output format for all generated files: 'ts' or 'js'
    'output_format' => env('TYPE_BRIDGE_OUTPUT_FORMAT', 'ts'),

    // Max line length for ESLint disable directive
    // Set to 0 or negative to disable
    'max_line_length' => env('TYPE_BRIDGE_MAX_LINE_LENGTH', 120),

    // Whether to include trailing commas in generated objects and arrays
    'trailing_commas' => env('TYPE_BRIDGE_TRAILING_COMMAS', true),

    // Enum generation configuration
    'enums' => [
        // Whether to generate all backed enums by default or only those with #[GenerateEnum]
        'generate_backed_enums' => true,

        // Output path (relative to resources directory)
        'output_path' => 'js/enums/generated',

        // Base import path for generated enums
        'import_base' => '@/enums/generated',

        // Discovery configuration
        'discovery' => [
            'include_paths' => [
                app_path('Enums'),
            ],
            // Exclude specific enums (by short name or FQCN)
            'exclude_paths' => [],
        ],
    ],

    // Translation generation configuration
    'translations' => [
        // Output path (relative to resources directory)
        'output_path' => 'js/lang/generated',

        // Discovery configuration
        'discovery' => [
            'include_paths' => [
                base_path('lang'),
            ],
            'exclude_paths' => [],
        ],

        // Custom adapter class (optional)
        'custom_adapter' => null,
    ],

    // Target i18n library for syntax transformation
    // Options: 'i18next' (default), 'vue-i18n'
    'i18n_library' => env('TYPE_BRIDGE_I18N_LIBRARY', 'i18next'),

    // Enum translator generation configuration
    'enum_translators' => [
        'discovery' => [
            'include_paths' => [
                app_path('Enums'),
            ],
            'exclude_paths' => [],
        ],
        'translator_output_path' => 'js/composables/generated',
        'utils_composables_output_path' => 'js/composables',
        'utils_composables_import_path' => '@/composables',
        'utils_lib_output_path' => 'js/lib',
        'utils_lib_import_path' => '@/lib',
    ],
];

```

Notes:
- Set `TYPE_BRIDGE_MAX_LINE_LENGTH=0` to disable `/* eslint-disable max-len */` insertion entirely.
- Defaults are merged even if you don’t publish the config.
- Discovery looks for enums under the configured `paths` only. Set `type-bridge.enums.generate_backed_enums=false` to restrict output to enums marked with `#[GenerateEnum]`.

## Supported translation engines

The translator utilities generated by this package can work with multiple i18n libraries through a tiny "engine" interface (an object exposing `t(key: string): string`). Out of the box we support:

- i18next
- react-i18next (uses the same i18next engine)
- vue-i18n

Tip: In `config/type-bridge.php` you can set `i18n_library` to control how translation keys are generated/organized for your target library. The default `i18next` also covers `react-i18next`.

## Usage

### Basic Enum Generation

Create a backed enum in your Laravel application:

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

Generate the frontend enum (TypeScript by default):

```bash
php artisan type-bridge:enums
```

This will create a TypeScript file at `resources/js/enums/generated/Status.ts`:

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

To generate JavaScript instead of TypeScript:

```bash
php artisan type-bridge:enums --format=js
```

### Check mode (CI drift detection)

Validate that your previously generated frontend enum files are still in sync with the current PHP enums without writing any files. This is ideal for CI to detect drift.

Run:

```bash
php artisan type-bridge:enums --check [--format=ts|js]
```

Behavior:

- Discovers current PHP enums and computes the expected frontend entries.
- Loads the previously generated frontend files from your configured output path.
- Compares both keys and values.
  - New case → reported as added: `+ KEY: VALUE`
  - Removed case → reported as removed: `- KEY: VALUE`
  - Changed value → reported as both add and remove: `+ KEY: NEW_VALUE` and `- KEY: OLD_VALUE`
- Exit codes:
  - 0 when everything is in sync
  - 1 when any difference is detected (suitable for failing CI)

Notes:

- `--format` controls which frontend files to compare against by extension (`ts` or `js`). If omitted, the command uses your configured `type-bridge.output_format`.
- Output lines are plain text by default for stable logs. In decorated terminals (e.g., running with `--ansi`), added lines render in green and removed lines in red. The enum header line intentionally remains unstyled.

Examples

In sync:

```text
Checking enums against previously generated frontend files...
✅ Enums are in sync with generated frontend files.
```

Differences found:

```text
❌ Enums differ from generated frontend files:

OrderStatus (resources/js/enums/generated/OrderStatus.ts)
  + SHIPPED: 'shipped'
  - CANCELLED: 'cancelled'

Run `php artisan type-bridge:enums --format=ts` to regenerate.
```

Value change:

```text
❌ Enums differ from generated frontend files:

Status (resources/js/enums/generated/Status.ts)
  + PENDING: 'awaiting'
  - PENDING: 'pending'

Run `php artisan type-bridge:enums --format=ts` to regenerate.
```

Output (`resources/js/enums/generated/Status.js`):

```js
// !!!!
// This is a generated file.
// Do not manually change this file
// !!!!

export const Status = {
    Active: 'active',
    Inactive: 'inactive',
    Pending: 'pending',
};
```

### Dirty mode (generate only new/changed enums)

Generate only enums that are missing or out of sync with the frontend output, using the same drift criteria as `--check`. This is useful for incremental builds or large projects.

Run:

```bash
php artisan type-bridge:enums --dirty [--format=ts|js]
```

Behavior:

- Computes diffs exactly like `--check` (missing files, added/removed cases, or changed values).
- Writes only the enums that are dirty.
- Prints `No dirty enums found.` when everything is already in sync.

### Opt-in Enum Generation

Use the `#[GenerateEnum]` attribute to explicitly mark enums for generation:

```php
<?php

namespace App\Enums;

use GaiaTools\TypeBridge\Attributes\GenerateEnum;

#[GenerateEnum]
enum ThemeVisibility: string
{
    case Private = 'private';
    case Unlisted = 'unlisted';
    case Public = 'public';
}
```


## Available Commands

```bash
# Publish config with smart detection
# Automatically detects TypeScript/JavaScript and your i18n library!
php artisan type-bridge:publish
```

### Generate Everything (enums + translations + translators)

```bash
# Generate all enums, translations, and enum translators
php artisan type-bridge:generate

# Generate everything for a specific locale
php artisan type-bridge:generate en

# Limit generation to specific enums (short name or FQCN)
php artisan type-bridge:generate --enums=Status --enums=App\\Enums\\Role

# Use a separate format for translations (ts|js|json)
php artisan type-bridge:generate --translations-format=json
```

### Generate Enums

```bash
# Generate enums using the default format from config (ts or js)
php artisan type-bridge:enums

# Generate enums explicitly as JavaScript
php artisan type-bridge:enums --format=js

# Generate enums explicitly as TypeScript
php artisan type-bridge:enums --format=ts

# Generate only new/changed enums (based on --check drift rules)
php artisan type-bridge:enums --dirty
```

### Generate Translations

```bash
# Generate translations for a locale (outputs to resources/js/lang/generated)
php artisan type-bridge:translations en

# Generate translations as JSON instead of TS (json|js|ts)
php artisan type-bridge:translations en --format=json

# Generate "flat" translation keys instead of nested
php artisan type-bridge:translations en --flat
```

Translation output examples (for locale `en`):

- TypeScript (`resources/js/lang/generated/en.ts`)

```ts
// !!!!
// This is a generated file.
// Do not manually change this file
// !!!!

export const en = {
    common: {
        ok: "OK",
        cancel: "Cancel",
    }
} as const;

export type en = typeof en;
```

### Publish Translator Utilities (frontend helpers)

These reusable helpers are required by the generated enum translators. Run once to publish them into your frontend source tree.

```bash
# Publishes:
# - composables/useTranslator.(ts|js)
# - lib/createEnumTranslationMap.(ts|js)
# - lib/translators.(ts|js)
php artisan type-bridge:publish-translator-utils [--force]
```

Defaults (can be changed in config/type-bridge.php → enum_translators.*):

- utils_composables_output_path: resources/js/composables
- utils_lib_output_path: resources/js/lib

The file extensions follow your `type-bridge.output_format` (ts by default).

### Generate Enum Translators

Generate per-enum translator composables/functions that map enum values to translated labels using your configured i18n library.

```bash
# Uses the global output format (ts|js)
php artisan type-bridge:enum-translators

# Force a specific format
php artisan type-bridge:enum-translators --format=ts
php artisan type-bridge:enum-translators --format=js

# Dry-run: discover candidates and show eligibility without writing files
php artisan type-bridge:enum-translators --dry
```

By default files are written to:

- resources/js/composables/generated (config: enum_translators.translator_output_path)

Dry-run output columns:

- Enum: FQCN of the PHP enum
- Prefix: Translation key prefix that will be used
- In FE generation set: Whether this enum is part of your frontend enums set
- Has translations: Whether any translations exist for that prefix

Only enums that are both in the FE generation set and have translations are eligible for generation.

## Global translation engine setup (app.ts/js)

The generated translators call a global engine. You must configure it once during your app bootstrapping by calling `configureTranslationEngine`.

### TypeScript (app.ts / main.ts / app.tsx)

```ts
// i18next (also works for react-i18next)
import i18n from '@/i18n';
import { configureTranslationEngine } from '@/composables/useTranslator';

configureTranslationEngine({
  t: (key: string) => i18n.t(key),
});

// React + react-i18next note:
// Prefer using the shared i18next instance you initialize for your app (as above).
// If you export that instance from your i18n setup file, this works in both React and non-React code.

// vue-i18n
import { createApp } from 'vue';
import { createI18n } from 'vue-i18n';
import App from './App.vue';
import { configureTranslationEngine } from '@/composables/useTranslator';

const i18nVue = createI18n({ /* ... */ });
const app = createApp(App).use(i18nVue);

// After vue-i18n is registered
configureTranslationEngine({
  t: (key: string) => i18nVue.global.t(key) as string,
});

app.mount('#app');
```

### JavaScript (app.js / main.js)

```js
// i18next (also works for react-i18next)
import i18n from '@/i18n';
import { configureTranslationEngine } from '@/composables/useTranslator';

configureTranslationEngine({
  t: (key) => i18n.t(key),
});

// vue-i18n
import { createApp } from 'vue';
import { createI18n } from 'vue-i18n';
import App from './App.vue';
import { configureTranslationEngine } from '@/composables/useTranslator';

const i18nVue = createI18n({ /* ... */ });
const app = createApp(App).use(i18nVue);

configureTranslationEngine({
  t: (key) => i18nVue.global.t(key),
});

app.mount('#app');
```

Once configured, any generated translator like `useStatusTranslator()` will use the global engine automatically:

```ts
import { useTranslator } from '@/composables/useTranslator';
import { createEnumTranslationMap } from '@/lib/createEnumTranslationMap';
import { Status } from '@/enums/generated/Status';

const translations = createEnumTranslationMap(Status, 'Status');
const tStatus = useTranslator(translations); // uses the globally configured engine
```

## Using the translator utilities

After publishing the utilities and generating translators, you can translate enum values in the frontend.

Global engine configuration (once at app bootstrap) is required. See “Global translation engine setup” above. You can also use the lightweight wrappers from `lib/translators`:

```ts
// Vue i18n helper
import { createI18n } from 'vue-i18n';
import { createVueI18nTranslator } from '@/lib/translators';
import { configureTranslationEngine } from '@/composables/useTranslator';

const i18n = createI18n({ /* ... */ });
configureTranslationEngine(createVueI18nTranslator(i18n));
```

Manual configuration using i18next works similarly:

```ts
import i18n from '@/i18n';
import { configureTranslationEngine } from '@/composables/useTranslator';

configureTranslationEngine({ t: (key) => i18n.t(key) });
```

### Example: translating a generated enum

If you generated the `Status` enum and have translations under the `Status.*` namespace, you can build a translator on the fly:

```ts
import { Status } from '@/enums/generated/Status';
import { createEnumTranslationMap } from '@/lib/createEnumTranslationMap';
import { useTranslator } from '@/composables/useTranslator';

const statusMap = createEnumTranslationMap(Status, 'Status');
const tStatus = useTranslator(statusMap);

tStatus(Status.Active); // → "Active"

// Build select options
const options = tStatus.options();
// → [{ value: 'active', label: 'Active' }, ...]

// Check if a value has a translation mapping
const hasPending = tStatus.has(Status.Pending); // true/false
```

You can also override the engine for a specific translator call:

```ts
const custom = useTranslator(statusMap, { t: (k) => myCustomFn(k) });
```

### Notes and configuration

- Configure your target i18n library via `type-bridge.i18n_library` (supports `i18next` and `vue-i18n`).
- Control where generated translator files and utilities are written via `enum_translators.*` keys in `config/type-bridge.php`:
  - translator_output_path
  - utils_composables_output_path / utils_composables_import_path
  - utils_lib_output_path / utils_lib_import_path
- The generators respect `type-bridge.output_format` for TS/JS.

- JavaScript (`resources/js/lang/generated/en.js`)

```js
// !!!!
// This is a generated file.
// Do not manually change this file
// !!!!

export const en = {
    common: {
        ok: "OK",
        cancel: "Cancel",
    }
};
```

- JSON (`resources/js/lang/generated/en.json`)

```json
{
  "common": {
    "ok": "OK",
    "cancel": "Cancel"
  }
}
```

## GenerateEnum Attribute Options

The `#[GenerateEnum]` attribute accepts the following options:

- `requiresComments` (bool): Include PHPDoc comments in generated output

## License

MIT
