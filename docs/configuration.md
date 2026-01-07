---
title: Configuration
---

# Configuration

The configuration file is located at `config/type-bridge.php`. It allows you to customize output paths, discovery rules, and target i18n libraries.

## General Options

```php
return [
    // Output format for all generated files: 'ts' or 'js'
    'output_format' => env('TYPE_BRIDGE_OUTPUT_FORMAT', 'ts'),

    // Max line length for ESLint disable directive
    'max_line_length' => env('TYPE_BRIDGE_MAX_LINE_LENGTH', 120),

    // Whether to include trailing commas in generated objects and arrays
    'trailing_commas' => env('TYPE_BRIDGE_TRAILING_COMMAS', true),
];
```

::: details Advanced General Options
- **`max_line_length`**: Set to `0` or negative to disable the `/* eslint-disable max-len */` insertion entirely.
:::

## Enums Configuration

Configure how and where your enums are discovered and generated.

```php
'enums' => [
    'generate_backed_enums' => true,
    'output_path' => 'js/enums/generated',
    'import_base' => '@/enums/generated',
    'discovery' => [
        'include_paths' => [
            app_path('Enums'),
        ],
        'exclude_paths' => [],
    ],
],
```

### Discovery Details
- **`generate_backed_enums`**: 
    - `true` (default): Generates all backed enums found in the configured paths.
    - `false`: Generates **only** enums marked with the `#[GenerateEnum]` attribute.
- **`output_path`**: Where generated enum files will be written (relative to your `resources` directory).
- **`include_paths`**: An array of directories where the package should look for PHP enums.
- **`exclude_paths`**: Exclude specific enums by their short name or Fully Qualified Class Name (FQCN).

## Translations Configuration

```php
'translations' => [
    'output_path' => 'js/lang/generated',
    'discovery' => [
        'include_paths' => [
            base_path('lang'),
        ],
        'exclude_paths' => [],
    ],
    'custom_adapter' => null,
],
```

### Translation Details
- **`output_path`**: Where generated translation files will be written (relative to your `resources` directory).
- **`include_paths`**: Where to discover Laravel translations. Supports strings, arrays, or glob patterns (e.g., `base_path('Modules/*/Resources/lang')`).
- **`custom_adapter`**: Provide your own adapter class for unique requirements. See [Adapters](./adapters.md) for implementation details.

## i18n Library

```php
'i18n_library' => env('TYPE_BRIDGE_I18N_LIBRARY', 'i18next'),
```

Target library for syntax transformation. Supported: `i18next`, `vue-i18n`.

## Enum Translators Configuration

Controls the generation of translator composables and utility paths.

```php
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
```

::: warning
Ensure your `resources/js` directory structure matches these paths or update the config to suit your project.
:::
