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
- time-ordered UUIDs with date, UUID-prefix, or custom upload directories;
- UUID or hash filenames, configurable globally and per field;
- picker results stored as media IDs, UUIDs, filenames, or complete paths.

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

Set `picker.modal_width` in the config or override it per field with Filament's `Width` enum:

```php
use Filament\Support\Enums\Width;

MediaPicker::make('cover_media_id')->images()->modalWidth(Width::Screen);
```

The default `normal` preserves the current size. `screen` fills the viewport with a small margin; standard widths such as `5xl` / `Width::FiveExtraLarge` are also supported.

Store the disk-relative file path instead of the media ID when needed:

```php
MediaPicker::make('cover_path')
    ->images()
    ->resultType(MediaPicker::RESULT_PATH)
    ->disk('public');
```

Configure a field independently of application defaults:

```php
MediaPicker::make('cover_uuid')
    ->images()
    ->resultType(MediaPicker::RESULT_UUID)
    ->uploadPath('posts/covers')
    ->uploadPathType('uuid_prefix')
    ->uploadFilenameType('uuid');
```

The default upload path is `uploads/{Y}/{m}/{d}/{uuid}.{extension}`. Set `FILAMENT_MEDIA_UPLOAD_PATH_TYPE=uuid_prefix` for `uploads/{uuid first 2 characters}/{filename}`, or `flat` for an exact base directory. `FILAMENT_MEDIA_FILENAME_TYPE` chooses `uuid` (default) or `hash`. Set `FILAMENT_MEDIA_UPLOAD_PATH=` for an empty base directory.

Run `php artisan migrate` after upgrading to add UUIDs to existing media rows. Existing files retain their locations and filenames.

See [docs/index.md](docs/index.md) for the complete documentation.
