# Filament Media documentation

`sarmadict/filament-media` is a Composer-installable Laravel package and Filament 5 panel plugin. It separates filesystem operations from the database registry while providing a reusable media picker for Filament forms.

Documentation:

1. [Installation](installation.md)
2. [Configuration](configuration.md)
3. [Uploads and filesystem behavior](uploads-and-filesystems.md)
4. [Filament browser and plugin](filament-plugin.md)
5. [Media picker](media-picker.md)
6. [Models and attachments](models-and-attachments.md)
7. [Authorization](authorization.md)
8. [Deletion protection and usage resolution](deletion-protection.md)
9. [Architecture and extension points](architecture.md)
10. [Migration from the former app module](migration-from-app-module.md)
11. [Package publishing](publishing.md)
12. [Testing](testing.md)

## Compatibility

The package targets PHP 8.3+, Filament 5, Livewire 4, and Laravel components 12 or 13. The bundled Nasra application uses Laravel 13 and Filament 5.7.

## Core concepts

The filesystem is authoritative for physical directories and files. The `media_files` table is the registry used by application records and the `MediaPicker`. A physical file can exist without a registry record; the browser can register it. Uploads performed through the package create both the physical file and its registry record.

The package service provider is discovered by Laravel through the `extra.laravel.providers` entry in the package `composer.json`. The Filament panel plugin is intentionally registered per panel in the consuming application's `PanelProvider`.
