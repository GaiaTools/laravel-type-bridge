---
title: Writers
---

# Writers

The Writer handles the physical creation of files on disk.

**Class**: `GaiaTools\TypeBridge\Writers\GeneratedFileWriter`

## How they work

The writer is the final stage of the pipeline. It:

1. Takes a `GeneratedFile` Value Object (which contains the path and the content).
2. Ensures that the target directory exists.
3. Writes the content to the file using Laravel's `File` facade.

By default, the package uses `GeneratedFileWriter`, but you can replace it in the service container if you need to redirect output or add custom logging.
