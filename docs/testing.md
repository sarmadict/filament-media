# Testing

The package includes Pest / Orchestra Testbench scaffolding.

Install development dependencies from the package root:

```bash
composer install
```

Run tests:

```bash
composer test
```

Format check:

```bash
composer format:check
```

## Important cases to keep covered

- `UploadPathResolver` builds `base/Y/m/d` paths.
- Empty upload base path produces `Y/m/d`.
- Date directories can be disabled.
- Invalid relative path segments are rejected.
- `FileUploader` stores a UUID filename and preserves `original_name` in the registry.
- `MediaRegistrar` restores a soft-deleted record for an existing disk/path.
- Files in use cannot be deleted.
- Empty directories can be deleted while non-empty directories are protected.
- Directory rename updates registered media paths and rolls back on failure.
- Disk allow-lists are respected by the browser and picker.
- Laravel Gate permissions protect page access and mutating actions.
- Package configuration, views, translations, migrations, and Livewire component are registered by the service provider.
