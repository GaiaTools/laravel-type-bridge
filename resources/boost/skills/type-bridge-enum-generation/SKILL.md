---
name: type-bridge-enum-generation
description: Generate TypeScript/JavaScript enums from PHP backed enums in Laravel applications.
---

# Laravel Type Bridge - Enum Generation

## When to use this skill
Use this skill when you need to:
- Generate frontend enum files from PHP enums
- Set up enum synchronization between backend and frontend
- Use the opt-in attribute for specific enums
- Run CI drift detection

## Overview

This package (`gaiatools/laravel-type-bridge`) generates TypeScript/JavaScript enums from PHP backed enums. Check the actual configuration in `config/type-bridge.php` for paths and output settings.

## Available Commands

```bash
# Generate enums using default format from config
php artisan type-bridge:enums

# Generate enums as JavaScript
php artisan type-bridge:enums --format=js

# Generate enums as TypeScript
php artisan type-bridge:enums --format=ts

# Check mode - validate enums without writing (CI drift detection)
php artisan type-bridge:enums --check

# Dirty mode - generate only new/changed enums
php artisan type-bridge:enums --dirty
```

## Opt-in Enum Generation

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

## GenerateEnum Attribute Options

```php
use GaiaTools\TypeBridge\Attributes\GenerateEnum;

// Basic usage - generate the enum
#[GenerateEnum]
enum Status: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}

// Include PHPDoc comments in generated output
#[GenerateEnum(requiresComments: true)]
enum Status: string { ... }

// Generate with custom output format
#[GenerateEnum(outputFormat: 'js')]
enum Status: string { ... }

// Include enum groups (public static methods returning arrays)
#[GenerateEnum(includeMethods: ['staffRoles', 'memberRoles'])]
enum UserRole: string { ... }
```

## Enum Groups

Enum groups export curated subsets alongside the base enum. Define public static methods that return arrays:

```php
<?php

namespace App\Enums;

use GaiaTools\TypeBridge\Attributes\GenerateEnum;

#[GenerateEnum(includeMethods: ['staffRoles', 'memberRoles'])]
enum UserRole: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Support = 'support';
    case Member = 'member';
    case Guest = 'guest';

    /** @return array<int, self> */
    public static function staffRoles(): array
    {
        return [self::Admin, self::Manager, self::Support];
    }

    /** @return array<int, string> */
    public static function memberRoles(): array
    {
        return [self::Member->value, self::Guest->value];
    }
}
```

Generated TypeScript output:

```typescript
export const UserRole = {
    Admin: 'admin',
    Manager: 'manager',
    Support: 'support',
    Member: 'member',
    Guest: 'guest',
} as const;

export type UserRole = typeof UserRole[keyof typeof UserRole];

export const StaffRoles = {
    Admin: UserRole.Admin,
    Manager: UserRole.Manager,
    Support: UserRole.Support,
} as const;

export const MemberRoles = [
    UserRole.Member,
    UserRole.Guest,
] as const;
```

## CI Integration

Use `--check` mode in CI to detect drift between PHP enums and generated frontend files:

```bash
php artisan type-bridge:enums --check
# Exit code 0 if in sync, 1 if differences found
```
