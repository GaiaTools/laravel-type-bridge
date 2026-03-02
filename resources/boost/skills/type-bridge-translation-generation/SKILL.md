---
name: type-bridge-translation-generation
description: Generate TypeScript/JavaScript/JSON translation files from Laravel translation files.
---

# Laravel Type Bridge - Translation Generation

## When to use this skill
Use this skill when you need to:
- Generate frontend translation files from Laravel lang files
- Convert PHP translation arrays to TypeScript, JavaScript, or JSON
- Set up i18n libraries with Laravel translations

## Overview

This package generates frontend translation files from Laravel's lang files. Check `config/type-bridge.php` for discovery paths and output settings.

## Available Commands

```bash
# Generate translations for a locale
php artisan type-bridge:translations en

# Generate translations as JSON
php artisan type-bridge:translations en --format=json

# Generate translations as JavaScript
php artisan type-bridge:translations en --format=js

# Generate translations as TypeScript
php artisan type-bridge:translations en --format=ts

# Generate "flat" translation keys instead of nested
php artisan type-bridge:translations en --flat
```

## Generate Everything Command

Generate enums, translations, and translators in one command:

```bash
# Generate all
php artisan type-bridge:generate

# Generate for specific locale
php artisan type-bridge:generate en

# Limit to specific enums
php artisan type-bridge:generate --enums=Status --enums=App\\Enums\\Role

# Use different format for translations
php artisan type-bridge:generate --translations-format=json
```

## i18n Library Support

The package supports two i18n libraries. Set the target library in `config/type-bridge.php` under `i18n_library`:

- `i18next` - Works with i18next, react-i18next, Node.js projects
- `vue-i18n` - Works with Vue.js projects using vue-i18n
