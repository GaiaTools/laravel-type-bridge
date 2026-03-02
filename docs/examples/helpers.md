---
title: Translation Helper Examples
---

# Translation Helper Examples

Translation helpers bridge the gap between enum values and their human-readable labels, using your frontend i18n library.

### Prerequisites

For a translation helper to be generated and function correctly, you need:

1.  **Generated Enums**: The enum must be marked for frontend generation. See [Enum Examples](enums).
2.  **Generated Translations**: The enum must have translations in your Laravel `lang` files, and they must be generated in the frontend. See [Translation Examples](translations).

The generator (`type-bridge:enum-translators`) will automatically skip enums that are not part of your frontend generation set or are missing translations. You can use the `--dry` flag to see why an enum might be skipped.

### Frontend Output

Running `php artisan type-bridge:enum-translators` generates a composable (hook) that you can use in your components.

```typescript
// resources/js/composables/generated/useUserRoleTranslator.ts

// !!!!
// This is a generated file.
// Do not manually change this file
// !!!!

import { useTranslator } from '@/composables/useTranslator';
import { UserRole } from '@/enums/generated/UserRole';
import { createEnumTranslationMap } from '@/lib/createEnumTranslationMap';

export function useUserRoleTranslator() {
    const translations = createEnumTranslationMap(UserRole, 'UserRole');
    return useTranslator(translations);
}
```

### Usage in Frontend

After [configuring your translation engine](../getting-started#2-setup-translation-utilities), you can use the generated helper in your components.

```typescript
import { useUserRoleTranslator } from '@/composables/generated/useUserRoleTranslator';
import { UserRole } from '@/enums/generated/UserRole';

const roleTranslator = useUserRoleTranslator();

// Get a single label
console.log(roleTranslator(UserRole.Admin)); // "Administrator"

// Get all options for a select input
const options = roleTranslator.options();
// Filter options to a subset of enum values
const memberOptions = roleTranslator.options([
    UserRole.Admin,
    UserRole.Editor,
]);
// [
//   { value: 'admin', label: 'Administrator' },
//   { value: 'editor', label: 'Content Editor' },
//   { value: 'viewer', label: 'Guest Viewer' }
// ]
```
