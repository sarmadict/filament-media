# Filament browser and plugin

Register the plugin in a panel provider:

```php
use Sarmadict\FilamentMedia\FilamentMediaPlugin;

return $panel
    ->plugin(FilamentMediaPlugin::make());
```

The plugin registers `Sarmadict\FilamentMedia\Filament\Pages\MediaLibrary`.

## Browser features

The page supports:

- Laravel filesystem disk switching;
- per-folder search;
- filtering by images, video, audio, documents, archives, and other files;
- grid and list views;
- pagination;
- folder creation;
- single-click folder selection and double-click opening in the current UI;
- directory context actions;
- directory rename;
- deletion of empty directories;
- physical file registration;
- file previews for supported image/video/audio files;
- file metadata display;
- file upload;
- safe file deletion with usage checks.

## Disabling the browser

If you only want the package services and `MediaPicker`:

```php
'navigation' => [
    'enabled' => false,
],
```

You may also simply omit `FilamentMediaPlugin` from a particular panel.
