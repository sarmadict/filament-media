# Configuration

Publish the config:

```bash
php artisan vendor:publish --tag=filament-media-config
```

The configuration file is `config/filament-media.php`.

## Models

```php
'models' => [
    'media_file' => MediaFile::class,
    'media_attachment' => MediaAttachment::class,
    'user' => config('auth.providers.users.model'),
],
```

Custom media models must extend the package models. This is useful when a host application needs its own policies, relationships, traits, or accessors.

## Tables

```php
'tables' => [
    'media_files' => 'media_files',
    'media_attachments' => 'media_attachments',
    'users' => 'users',
],
```

Set these before the package migration is run. Changing them after production data exists requires an application migration.

## Uploads

```php
'upload' => [
    'disk' => env('FILAMENT_MEDIA_DISK', 'public'),
    'path' => env('FILAMENT_MEDIA_UPLOAD_PATH', 'uploads'),
    'path_type' => env('FILAMENT_MEDIA_UPLOAD_PATH_TYPE', 'date'), // date, uuid_prefix, flat
    'filename_type' => env('FILAMENT_MEDIA_FILENAME_TYPE', 'uuid'), // uuid, hash
    'visibility' => env('FILAMENT_MEDIA_VISIBILITY'),
    'date_format' => env('FILAMENT_MEDIA_DATE_FORMAT', 'Y/m/d'),
],
```

`FILAMENT_MEDIA_DISK` is the authoritative disk for new uploads and for registering existing physical files into `media_files`. Other allowed disks remain browsable, but cannot receive uploads or new media registrations.

Default output:

```text
uploads/2026/12/10/019a1234-5678-7000-8000-123456789012.jpg
```

To produce exactly a date-rooted layout:

```dotenv
FILAMENT_MEDIA_UPLOAD_PATH=
```

Result:

```text
2026/12/10/019a1234-5678-7000-8000-123456789012.jpg
```

Choose the hierarchy independently of the filename:

| `upload.path_type` | Layout |
| --- | --- |
| `date` | `{base}/{date format}/{filename}` |
| `uuid_prefix` | `{base}/{media UUID first 2 characters}/{filename}` |
| `flat` | `{base}/{filename}` |

`upload.filename_type=uuid` uses the unique UUIDv7 saved in `media_files.uuid`. `hash` uses a random 40-character alphanumeric name, not a content checksum. Both append the lowercase extension and retain the client filename in `original_name`. The prefix directory always comes from the media UUID, even when the filename is a hash.

Example UUID-prefix configuration:

```dotenv
FILAMENT_MEDIA_UPLOAD_PATH_TYPE=uuid_prefix
FILAMENT_MEDIA_FILENAME_TYPE=uuid
```

This produces `uploads/01/019a1234-5678-7000-8000-123456789012.jpg` for the UUID above. `flat` selects an exact custom directory without appending a hierarchy. Per-field overrides are documented in [media-picker.md](media-picker.md).

`upload.path_type` is the single hierarchy setting and defaults to `date`. To store directly in the base directory:

```dotenv
FILAMENT_MEDIA_UPLOAD_PATH_TYPE=flat
```

`upload.date_format` (`FILAMENT_MEDIA_DATE_FORMAT`) applies only to `date` paths. For example, `Y/m` groups uploads by month.

When upgrading, replace `FILAMENT_MEDIA_DATE_DIRECTORIES=false` with `FILAMENT_MEDIA_UPLOAD_PATH_TYPE=flat` and move a published `upload.date_directories.format` value to `upload.date_format`. The old date-directory settings are no longer read.

Run `php artisan migrate` after upgrading to add/backfill UUIDs in existing media rows; existing file locations remain unchanged.

## Picker defaults

```php
'picker' => [
    'result_type' => 'id',
],
```

Supported values are `id`, `media_id`, `uuid`, `file_name`, `path`, and `full_path`. ID remains the default; `media_id` aliases `id`, and `full_path` aliases the disk-relative `path`. Each field's `resultType()` takes precedence.

## Disk allow-list

An empty list exposes all Laravel filesystem disks:

```php
'disks' => [
    'allowed' => [],
],
```

Restrict the browser and picker:

```php
'disks' => [
    'allowed' => ['public', 's3'],
],
```

## Direction

```php
'ui' => [
    'direction' => null,
],
```

When `null`, the package infers RTL for common RTL locales (`fa`, `ar`, `he`, `ur`) and LTR otherwise. Set `rtl` or `ltr` explicitly when required.

## Navigation

```php
'navigation' => [
    'enabled' => true,
],
```

Set `enabled` to `false` when you only want the `MediaPicker` and services without registering the full file-browser page in a panel.

## Service overrides

```php
'repository' => EloquentMediaRepository::class,
'preview_url_resolver' => DefaultPreviewUrlResolver::class,
'usage' => [
    'resolver' => ConfigurableMediaUsageResolver::class,
    // ...
],
```

Custom implementations are resolved through Laravel's service container.
