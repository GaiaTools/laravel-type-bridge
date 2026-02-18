---
title: API Reference
---

# API Reference

## Attributes

### Generate Enum Attribute

Apply the `#[GenerateEnum]` attribute to your PHP enums to customize their frontend generation.

```php
use GaiaTools\TypeBridge\Attributes\GenerateEnum;

#[GenerateEnum(
    requiresComments: true
)]
enum Status: string { ... }
```

| Parameter | Type | Default | Description |
| :--- | :--- | :--- | :--- |
| `requiresComments` | `bool` | `false` | Include PHPDoc comments in the generated output. |

### Generate Translator Attribute

Apply the `#[GenerateTranslator]` attribute to your PHP enums to customize their frontend translator generation.

```php
use GaiaTools\TypeBridge\Attributes\GenerateTranslator;

#[GenerateTranslator(
    translationKey: 'custom.prefix',
    generateComposable: true
)]
enum Status: string { ... }
```

| Parameter | Type | Default | Description |
| :--- | :--- | :--- | :--- |
| `translationKey` | `string` | `null` | Custom translation key prefix. Defaults to the enum's short name. |
| `generateComposable` | `bool` | `true` | Whether to generate a composable for this enum. Set to `false` to skip. |

## CLI Commands

### Publish Configuration
The `type-bridge:publish` command publishes the configuration file. It attempts to auto-detect your project's `output_format` and `i18n_library`.

### Generate Everything
The `type-bridge:generate {locale?}` command generates enums, translations, and enum translators in one step.

- `--format=ts|js`: Output format for enums and enum translators.
- `--translations-format=ts|js|json`: Output format for translations.
- `--flat`: Generate flat translation keys (e.g. `"auth.failed": "..."`) instead of nested objects.
- `--enums=*`: Limit generation to specific enums by short name or FQCN. Repeatable.

### Generate Enums
The `type-bridge:enums` command generates frontend enum files from discovered PHP enums.

- `--format=ts|js`: Override the configured output format.
- `--check`: (CI Mode) Check for drift between PHP and frontend files without writing. Returns exit code 1 if differences exist.
- `--dirty`: Generate only enums that are missing or out of sync with frontend files (same drift criteria as `--check`).

### Generate Translations
The `type-bridge:translations {locale?}` command generates frontend translation files for specified locale(s). If no locale is provided, it attempts to discover all locales in your project.

- `--format=ts|js|json`: Choose the output file format.
- `--flat`: Generate flat keys (e.g. `"auth.failed": "..."`) instead of nested objects.

### Publish Translator Utilities
The `type-bridge:publish-translator-utils` command publishes the core frontend utility files (composables and libs) needed for enum translations.

### Generate Enum Translators
The `type-bridge:enum-translators` command generates the per-enum translator composables.

::: info

For a translator to be generated, the enum must be included in your frontend generation set AND have matching translations in your Laravel language files. Use the `--dry` flag to see why an enum might be skipped.

:::

- `--format=ts|js`: Override the configured output format.
- `--dry`: Show which enums are eligible for translator generation without writing files.

## Frontend Utilities

### Configure Translation Engine
The `configureTranslationEngine(engine)` function configures the global translation engine used by generated translators.

```typescript
type Engine = {
    t: (key: string) => string;
};
```

### Translator Hook
The `useTranslator(map, options?)` hook (or function) creates a translator for a specific enum map.

### Create Enum Translation Map
The `createEnumTranslationMap(enum, prefix)` function creates a mapping between enum values and translation keys.
