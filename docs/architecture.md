---
title: Architecture
---

# Architecture

This section describes the internal architecture of Laravel Type Bridge. While many of these layers are not yet fully configurable via the main configuration file, understanding them is key to contributing or customizing the package in the future.

## The Generation Pipeline

All generation commands follow a consistent pipeline handled by a **Generator**:

1. **[Discover](./discoverers)**: Find the source items (PHP enums, translation files, etc.).
2. **[Transform](./transformers)**: Convert discovered items into an intermediate representation (Value Objects).
3. **[Format](./formatters)**: Turn the Value Objects into strings (TypeScript, JavaScript, or JSON).
4. **[Write](./writers)**: Save the formatted content to the filesystem.

## Core Abstractions

The pipeline is built on several key abstractions:

- **[Discoverers](./discoverers)**: Responsible for finding what needs to be generated.
- **[Transformers](./transformers)**: Convert raw items into structured Value Objects.
- **[Formatters](./formatters)**: Turn Value Objects into the final string output.
- **[Writers](./writers)**: Handle the physical creation of files on disk.
- **[Adapters](./adapters)**: Handle specialized transformations, such as i18n syntax.

## Future Flexibility

We aim to make the generators themselves more flexible in future versions, allowing you to swap Discoverers, Transformers, and Formatters directly via configuration without needing to override container bindings.
