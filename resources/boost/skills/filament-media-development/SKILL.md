---
name: filament-media-development
description: Integrate and customize sarmadict/filament-media in Laravel 12 or 13 applications using Filament 5. Use when installing the package, registering its panel plugin, adding MediaPicker fields or media relationships, configuring storage and authorization, protecting referenced media from deletion, or extending its repository and resolver contracts.
---

# Filament Media Development

## Package requirements

Use this skill for `sarmadict/filament-media`, which targets PHP 8.3+, Laravel 12 or 13, Filament 5, and Livewire 4.

The filesystem is authoritative for physical files and directories. The `media_files` table is a registry with numeric IDs, unique time-ordered UUIDv7 values, and metadata. A physical file may exist without a registry row; it must be registered before `MediaPicker` can select it. Run `php artisan migrate` after upgrading: the UUID migration backfills existing rows, including soft-deleted media, without renaming their files.

## Install and register

Install the package and run its automatically loaded migrations:

```bash
composer require sarmadict/filament-media
php artisan migrate
```

Laravel discovers `Sarmadict\FilamentMedia\FilamentMediaServiceProvider` through Composer. Do not register the service provider manually unless package discovery is disabled.

Register the plugin in every Filament panel that should expose the media browser:

```php
use Sarmadict\FilamentMedia\FilamentMediaPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(FilamentMediaPlugin::make());
}
```

Publishing configuration is optional. Publish only resources the application needs to override:

```bash
php artisan vendor:publish --tag=filament-media-config
php artisan vendor:publish --tag=filament-media-views
php artisan vendor:publish --tag=filament-media-translations
```

## Use the media picker

`MediaPicker` stores one active, registered media selection. It returns `media_files.id` by default; back an optional ID field with a nullable foreign key.

```php
use Sarmadict\FilamentMedia\Filament\Forms\Components\MediaPicker;

MediaPicker::make('cover_media_id')
    ->label('Cover')
    ->images();
```

Choose the result type on each field independently of its upload layout and filename type:

```php
MediaPicker::make('cover_uuid')
    ->resultType(MediaPicker::RESULT_UUID)
    ->disk('public')
    ->images();
```

| Result constant | Stored value | Application column |
| --- | --- | --- |
| `RESULT_ID` / `RESULT_MEDIA_ID` | `media_files.id` | Nullable numeric foreign key |
| `RESULT_UUID` | `media_files.uuid` | Nullable UUID / 36-character string |
| `RESULT_FILE_NAME` | `media_files.file_name`, including extension | Nullable string (255 characters) |
| `RESULT_PATH` / `RESULT_FULL_PATH` | `media_files.path`, including directories and filename | Nullable string (700 characters) |

`RESULT_ID` remains the default. Set `filament-media.picker.result_type` to change the application default; `resultType()` overrides it and accepts a closure. `RESULT_MEDIA_ID` and `RESULT_FULL_PATH` are aliases for the existing ID and path behaviors. A full path is disk-relative, not an absolute filesystem path or URL.

Specify `disk()` for filename- and path-backed fields. Paths can repeat across disks; without a field disk, paths resolve against the default disk first and then the other allowed disks. Ambiguous filenames return no selection rather than resolving to an arbitrary legacy file. Filename and path values do not update automatically after moves or renames. Use ID or UUID results for stable references. A UUID `BelongsTo` relationship must specify `uuid` as the owner key; ordinary relationships use the numeric ID. Configure `usage.direct_references` with the matching `media_attribute` for non-ID values.

Available MIME helpers are `images()`, `videos()`, `audio()`, and `documents()`. Use `acceptedMimeTypes([...])` for a custom set:

```php
MediaPicker::make('asset_media_id')
    ->acceptedMimeTypes([
        'image/*',
        'application/pdf',
    ]);
```

A typical direct relationship is:

```php
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Sarmadict\FilamentMedia\Models\MediaFile;

public function coverMedia(): BelongsTo
{
    return $this->belongsTo(MediaFile::class, 'cover_media_id');
}
```

If the application configures a custom media model, use that subclass in application relationships.

## Use polymorphic attachments

Add `HasMediaAttachments` to a model that needs reusable, collection-based media:

```php
use Sarmadict\FilamentMedia\Concerns\HasMediaAttachments;

class Post extends Model
{
    use HasMediaAttachments;
}
```

The trait exposes `mediaAttachments()` and `mediaFiles()`. Attachment pivots support `collection`, `sort_order`, `state`, and `created_by`.

Use a direct foreign key for a single semantic field such as a cover image. Use polymorphic attachments for collections or reusable attachment groups.

## Configure uploads and disks

The main environment settings are:

```dotenv
FILAMENT_MEDIA_DISK=public
FILAMENT_MEDIA_UPLOAD_PATH=uploads
FILAMENT_MEDIA_UPLOAD_PATH_TYPE=date
FILAMENT_MEDIA_FILENAME_TYPE=uuid
FILAMENT_MEDIA_VISIBILITY=public
FILAMENT_MEDIA_DATE_FORMAT=Y/m/d
```

The independent upload settings are:

- `upload.path`: base directory; an empty string means the disk root.
- `upload.path_type`: `date` appends the date hierarchy, `uuid_prefix` appends the first two characters of the registry UUID, and `flat` uses the exact base directory.
- `upload.filename_type`: `uuid` stores `{media_files.uuid}.{extension}`; `hash` stores a random 40-character alphanumeric name plus extension, not a content checksum.
- `upload.date_format`: PHP date format for the `date` hierarchy, default `Y/m/d`. It has no effect on `uuid_prefix` or `flat` paths.

The default path type is `date`, producing `uploads/Y/m/d/{uuid}.{extension}`; the client filename is retained in `media_files.original_name`. The UUID-prefix layout is `{base}/{uuid first 2 characters}/{uuid}.{extension}` in UUID filename mode. Hash filenames still shard by the registry UUID. Use `flat` to disable hierarchy. When upgrading an application, replace `FILAMENT_MEDIA_DATE_DIRECTORIES=false` with `FILAMENT_MEDIA_UPLOAD_PATH_TYPE=flat` and move any published `upload.date_directories.format` value to `upload.date_format`; the old date-directory settings are no longer read.

Override uploads per field to combine layouts within one app:

```php
MediaPicker::make('cover_uuid')
    ->images()
    ->resultType(MediaPicker::RESULT_UUID)
    ->uploadPath('posts/covers')
    ->uploadPathType('uuid_prefix')
    ->uploadFilenameType('uuid');

MediaPicker::make('document_path')
    ->documents()
    ->resultType(MediaPicker::RESULT_FULL_PATH)
    ->disk('public')
    ->uploadPath('documents')
    ->uploadPathType('date')
    ->uploadDateFormat('Y/m')
    ->uploadFilenameType('hash');

MediaPicker::make('avatar_media_id')
    ->images()
    ->uploadPath('profiles/avatars')
    ->uploadPathType('flat'); // Exact custom directory, with no hierarchy appended.
```

All four upload methods accept strings, closures evaluated in the Filament field context, or null to inherit the global setting. `uploadPath('')` explicitly selects the disk root; pair it with `flat` for root-level files. Field settings affect new uploads, not existing selections. Unset settings inherit package configuration, and the standalone media library uses the global settings.

For direct service uploads, `FileUploader::upload()` accepts named `directory`, `pathType`, `filenameType`, and `dateFormat` arguments. `UploadPathResolver::PATH_DATE`, `PATH_UUID_PREFIX`, and `PATH_FLAT` and `FileUploader::FILENAME_UUID` / `FILENAME_HASH` are available instead of literal strategy strings. Paths reject `.` and `..` segments.

`filament-media.upload.disk` is the only disk that accepts package uploads and registration of existing files. Other disks in `filament-media.disks.allowed` may be browsed but cannot receive uploads or new registry entries. An empty allow-list exposes all configured Laravel filesystem disks.

Public disks use `Storage::url()` for previews. Non-public disks use a temporary URL when the adapter supports it; otherwise no preview URL is returned.

## Configure authorization and safe deletion

Define the package's default Laravel Gate abilities unless the application already grants them through `Gate::before()`:

```text
media_files.view-any
media_files.create
media_files.update
media_files.delete
```

Keep package authorization enabled for panels accessible by untrusted users. Disable it only when an equivalent outer authorization layer protects every package action.

Attachment usage is checked before deletion by default. Direct foreign keys are not discoverable automatically, so list each one under `usage.direct_references`:

```php
'usage' => [
    'direct_references' => [
        [
            'model' => App\Models\Post::class,
            'column' => 'cover_media_id',
            'label' => 'Posts',
        ],
    ],
],
```

Do this for every application table that stores a media reference. ID is the default comparison. Set `media_attribute` to `uuid`, `file_name`, or `path` (`full_path` is also accepted) for other result types:

```php
[
    'model' => App\Models\Post::class,
    'column' => 'cover_uuid',
    'media_attribute' => 'uuid',
    'label' => 'Posts',
],
```

An optional `disk_column` names the application's disk column and limits filename/path usage to the matching disk. When usage exists, deletion raises `MediaInUseException` rather than removing the file.

## Extend the package

Custom media models must extend the corresponding package model. Configure model classes under `filament-media.models`.

The service container resolves these configurable extension points:

- `MediaRepository` via `filament-media.repository`
- `PreviewUrlResolver` via `filament-media.preview_url_resolver`
- `MediaUsageResolver` via `filament-media.usage.resolver`

Use a custom preview resolver for CDN or provider-specific signed URLs. Use a custom usage resolver when deletion checks must query domain services, external tables, or other databases.

For deeper behavior, inspect the installed documentation in `vendor/sarmadict/filament-media/docs/`, especially `configuration.md`, `media-picker.md`, `models-and-attachments.md`, `deletion-protection.md`, and `architecture.md`.

## Verify changes

After integration changes, run the application's relevant tests and formatter. For package development, use:

```bash
composer test
composer format:check
```
