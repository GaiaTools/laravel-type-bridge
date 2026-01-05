---
title: Overview
---

# Laravel Type Bridge

Laravel Type Bridge is a package for generating TypeScript or JavaScript enums and frontend translation files from your Laravel application. It bridges the gap between your backend definitions and frontend usage, ensuring type safety and consistency across your stack.

::: tip
This package is especially useful for Inertia.js projects or any decoupled frontend that consumes Laravel-driven data.
:::

## Key Features

- **Enum Synchronization**: Automatically discover and generate frontend enums from PHP backed enums.
- **Opt-in Generation**: Use the `#[GenerateEnum]` attribute to explicitly mark enums for generation.
- **Flexible Discovery**: Configurable paths to support various project structures, including Modules.
- **Translation Export**: Export Laravel translation files to TypeScript, JavaScript, or JSON formats compatible with popular i18n libraries.
- **CI Readiness**: Built-in check mode to detect drift between PHP enums and generated frontend files during CI/CD.
- **Translator Helpers**: Generate frontend utilities to map enum values to their translated labels.

## How it Works

1. You define your enums and translations in Laravel as usual.
2. You run a `php artisan` command.
3. The package generates clean, type-safe frontend files in your `resources/js` directory.

## Next Steps

- [Getting Started](./getting-started.md)
- [Configuration](./configuration.md)
- [API Reference](./api.md)
