---
title: Enum Examples
---

# Enum Examples

This page demonstrates how to set up PHP backed enums for generation and what the resulting frontend code looks like.

### Backend Setup

Define a PHP backed enum in your Laravel application. You can use the `#[GenerateEnum]` attribute to mark it for generation or customize its behavior.

See the [GenerateEnum Attribute](../api#generateenum) documentation for more details on available options.

```php
<?php

namespace App\Enums;

use GaiaTools\TypeBridge\Attributes\GenerateEnum;

#[GenerateEnum]
enum UserRole: string
{
    case Admin = 'admin';
    case Editor = 'editor';
    case Viewer = 'viewer';
}
```

### Frontend Output

Running `php artisan type-bridge:enums` generates a TypeScript file with a constant and a type definition.

```typescript
// resources/js/enums/generated/UserRole.ts

// !!!!
// This is a generated file.
// Do not manually change this file
// !!!!

export const UserRole = {
    Admin: 'admin',
    Editor: 'editor',
    Viewer: 'viewer',
} as const;

export type UserRole = typeof UserRole[keyof typeof UserRole];
```

## Enum Groups

Enum groups let you export curated subsets or mappings alongside the base enum. Define groups with `public static` methods and opt in using `#[GenerateEnum(includeMethods: [...])]`.

Rules:

- Each listed method must be `public static` with zero parameters.
- Methods must return an array. Sequential arrays become group arrays; associative arrays become group records.
- Values may be enum cases, backed values that match a case, or scalar/null literals.
- Group names are the method names converted to StudlyCase and must not collide with the enum name or other groups.

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

    /** @return array<int, self> */
    public static function memberRoles(): array
    {
        return [self::Member, self::Guest];
    }
}
```

```ts
export const UserRole = {
    Admin: 'admin',
    Manager: 'manager',
    Support: 'support',
    Member: 'member',
    Guest: 'guest',
} as const;

export type UserRole = typeof UserRole[keyof typeof UserRole];

export const StaffRoles = [
    UserRole.Admin,
    UserRole.Manager,
    UserRole.Support,
] as const;

export type StaffRoles = typeof StaffRoles[number];

export const MemberRoles = [
    UserRole.Member,
    UserRole.Guest,
] as const;

export type MemberRoles = typeof MemberRoles[number];
```
