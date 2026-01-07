---
title: Architecture
---

# Architecture

This section describes the internal architecture of Laravel Type Bridge. While many of these layers are not yet fully configurable via the main configuration file, understanding them is key to contributing or customizing the package in the future.

## The Generation Pipeline

All generation commands follow a consistent pipeline handled by a **Generator**:

1. **[Discover](./discoverers.md)**: Find the source items (PHP enums, translation files, etc.).
2. **[Transform](./transformers.md)**: Convert discovered items into an intermediate representation (Value Objects).
3. **[Format](./formatters.md)**: Turn the Value Objects into strings (TypeScript, JavaScript, or JSON).
4. **[Write](./writers.md)**: Save the formatted content to the filesystem.

## Core Abstractions

The pipeline is built on several key abstractions:

- **[Discoverers](./discoverers.md)**: Responsible for finding what needs to be generated.
- **[Transformers](./transformers.md)**: Convert raw items into structured Value Objects.
- **[Formatters](./formatters.md)**: Turn Value Objects into the final string output.
- **[Writers](./writers.md)**: Handle the physical creation of files on disk.
- **[Adapters](./adapters.md)**: Handle specialized transformations, such as i18n syntax.

## Future Flexibility

We aim to make the generators themselves more flexible in future versions, allowing you to swap Discoverers, Transformers, and Formatters directly via configuration without needing to override container bindings.
