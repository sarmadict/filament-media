# Migration from the former app module

The bundled application previously stored the implementation in `app/MediaLibrary` under the `App\MediaLibrary` namespace.

The extracted package moves that code to:

```text
packages/sarmadict/filament-media
```

with the namespace:

```text
Sarmadict\FilamentMedia
```

## Namespace changes

```text
App\MediaLibrary\MediaLibraryPlugin
    -> Sarmadict\FilamentMedia\FilamentMediaPlugin

App\MediaLibrary\Filament\Forms\Components\MediaPicker
    -> Sarmadict\FilamentMedia\Filament\Forms\Components\MediaPicker
```

All service, contract, exception, data, Livewire, and support namespaces change in the same way.

## Database migration compatibility

The package keeps the existing migration filename:

```text
2026_08_10_053129_create_media_files_table.php
```

For an existing database where that migration is already recorded, Laravel will not run it again. The migration also checks whether the target tables already exist before creating them.

## Host model compatibility

The bundled Nasra project retains thin `App\Models\MediaFile` and `App\Models\MediaAttachment` subclasses and configures the package to use them. This preserves existing policy discovery and application relationships without keeping media-library behavior in the app namespace.

A new application does not need these compatibility subclasses.

## Existing references

Replace imports in Filament resources:

```php
use Sarmadict\FilamentMedia\Filament\Forms\Components\MediaPicker;
```

Replace the panel plugin import:

```php
use Sarmadict\FilamentMedia\FilamentMediaPlugin;
```

Move application-specific media usage rules into `config/filament-media.php` under `usage.direct_references`.
