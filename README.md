# sarmadict/filament-media

A reusable media library for Laravel and Filament 5. It provides:

- a filesystem-aware Filament media browser;
- a reusable `MediaPicker` form field;
- uploads registered in a `media_files` table;
- polymorphic `media_attachments`;
- public / temporary preview URL resolution;
- safe file deletion with configurable usage checks;
- directory browsing, creation, rename, deletion, and registration;
- configurable disks and authorization;
- configurable date-based upload paths such as `media/2026/12/10/<uuid>.jpg`.

## Install

```bash
composer require sarmadict/filament-media
php artisan vendor:publish --tag=filament-media-config
php artisan migrate
```

Register the panel plugin:

```php
use Sarmadict\FilamentMedia\FilamentMediaPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(FilamentMediaPlugin::make());
}
```

Use the picker:

```php
use Sarmadict\FilamentMedia\Filament\Forms\Components\MediaPicker;

MediaPicker::make('cover_media_id')->images();
```

The default upload path is `media/{Y}/{m}/{d}`. Set `FILAMENT_MEDIA_UPLOAD_PATH=` to store directly under `{Y}/{m}/{d}`.

See [docs/index.md](docs/index.md) for the complete documentation.
