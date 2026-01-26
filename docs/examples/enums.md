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
