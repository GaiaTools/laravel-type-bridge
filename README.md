[![Quality gate](https://sonar.r2websolutions.com/api/project_badges/quality_gate?project=GaiaTools_laravel-type-bridge_65a0cd71-382c-4322-a628-a979f8beb401&token=sqb_320e3b18690817c1a4b370f3ea83f728851fb470)](https://sonar.r2websolutions.com/dashboard?id=GaiaTools_laravel-type-bridge_65a0cd71-382c-4322-a628-a979f8beb401)

# Gaia Tools Laravel Type Bridge

A Laravel package for generating TypeScript/JavaScript enums and frontend translation files from your Laravel app.

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
        // Output path (relative to resources directory)
        'output_path' => 'js/enums/generated',

        // Discovery configuration
        'discovery' => [
            'paths' => [
                app_path('Enums'),
            ],
            // When true: generates all backed enums
            // When false: generates ONLY enums with GenerateEnum attribute
            'generate_backed_enums' => true,
            // Exclude specific enums (by short name or FQCN)
            'excludes' => [],
        ],
    ],

    // Translation generation configuration
    'translations' => [
        // Output path (relative to resources directory)
        'output_path' => 'js/locales/generated',

        // Target i18n library for syntax transformation
        // Options: 'i18next' (default, works for react-i18next too), 'vue-i18n', 'laravel'
        'i18n_library' => env('TYPE_BRIDGE_I18N_LIBRARY', 'i18next'),

        // Custom adapter class (optional - for users who want to provide their own)
        'custom_adapter' => null, // e.g., \App\TypeBridge\CustomAdapter::class
    ],
];

```

Notes:
- Set `TYPE_BRIDGE_MAX_LINE_LENGTH=0` to disable `/* eslint-disable max-len */` insertion entirely.
- Defaults are merged even if you don’t publish the config.
- Discovery looks for enums under the configured `paths` only. Use `generateBackedEnums=false` to restrict output to enums marked with `#[GenerateEnum]`.

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

### Enum with Translator

Generate enums with automatic translation support:

```php
<?php

namespace App\Enums;

use GaiaTools\TypeBridge\Attributes\GenerateEnum;

#[GenerateEnum(hasTranslator: true)]
enum UserRole: string
{
    case Admin = 'admin';
    case User = 'user';
    case Guest = 'guest';
}
```

### Custom Output Format per Enum

Override the global output format for specific enums:

```php
<?php

namespace App\Enums;

use GaiaTools\TypeBridge\Attributes\GenerateEnum;

#[GenerateEnum(outputFormat: 'js')]
enum Priority: int
{
    case Low = 1;
    case Medium = 2;
    case High = 3;
}
```

## Available Commands

```bash
# Publish config with smart detection
# Automatically detects TypeScript/JavaScript and your i18n library!
php artisan type-bridge:publish
```

### Generate Enums

```bash
# Generate enums using the default format from config (ts or js)
php artisan type-bridge:enums

# Generate enums explicitly as JavaScript
php artisan type-bridge:enums --format=js

# Generate enums explicitly as TypeScript
php artisan type-bridge:enums --format=ts
```

### Generate Translations

```bash
# Generate translations for a locale (outputs to resources/js/locales/generated)
php artisan type-bridge:translations en

# Generate translations as JSON instead of TS (json|js|ts)
php artisan type-bridge:translations en --format=json

# Generate "flat" translation keys instead of nested
php artisan type-bridge:translations en --flat
```

Translation output examples (for locale `en`):

- TypeScript (`resources/js/locales/generated/en.ts`)

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

- JavaScript (`resources/js/locales/generated/en.js`)

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

- JSON (`resources/js/locales/generated/en.json`)

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
- `hasTranslator` (bool): Generate a translator for the enum
- `outputFormat` (string|null): Override global output format ('ts' or 'js')

## License

MIT
