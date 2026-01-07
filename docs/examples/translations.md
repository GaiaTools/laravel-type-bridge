---
title: Translation Examples
---

# Translation Examples

This page demonstrates how Laravel translation files are exported for use in your frontend.

### Backend Setup

Laravel translations can be defined in standard PHP or JSON files.

**lang/en/enums.php**
```php
<?php

return [
    'UserRole' => [
        'Admin' => 'Administrator',
        'Editor' => 'Content Editor',
        'Viewer' => 'Guest Viewer',
    ],
];
```

### Frontend Output

Running `php artisan type-bridge:translations en` exports your Laravel translations to a nested object structure.

```typescript
// resources/js/lang/generated/en.ts

// !!!!
// This is a generated file.
// Do not manually change this file
// !!!!

export const en = {
    UserRole: {
        Admin: 'Administrator',
        Editor: 'Content Editor',
        Viewer: 'Guest Viewer',
    },
} as const;

export type en = typeof en;
```
