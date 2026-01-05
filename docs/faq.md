---
title: FAQ & Troubleshooting
---

# FAQ & Troubleshooting

## Common Issues

### My enums are not being generated
Check if your enums are "Backed Enums" (e.g., `enum Status: string`). Pure enums without a backing type are not supported for generation as they don't have a stable value to export to JavaScript.

Also, ensure your enum directory is included in the `enums.discovery.include_paths` configuration.

### CI fails with "Enums differ from generated frontend files"
This happens when you've modified a PHP enum but haven't run the generation command. 

::: tip
Run `php artisan type-bridge:enums` locally and commit the updated frontend files to resolve this.
:::

### Translators are returning translation keys instead of labels
Ensure you have:
1. Configured the global translation engine in your app's entry point (`app.ts`).
2. Generated the translation files for the current locale using `type-bridge:translations`.
3. Verified that the translation keys in your Laravel app match the prefix used by the enum.

## Troubleshooting

### Debugging Enum Discovery
If enums are missing, you can temporarily set `generate_backed_enums` to `true` in your config to see if they appear. If they only appear when using the attribute, double-check your namespaces and paths.

### Overriding Generated Files
Do not manually edit files in the `generated` folders. These files are overwritten every time you run the generation commands. If you need custom logic, wrap the generated code in your own modules.

::: danger
Manually edited files in `generated/` directories will be LOST on the next command run.
:::

## Still having trouble?
If you encounter a bug or have a feature request, please [open an issue](https://github.com/GaiaTools/laravel-type-bridge/issues).
