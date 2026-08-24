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
    'visibility' => env('FILAMENT_MEDIA_VISIBILITY'),
    'date_directories' => [
        'enabled' => env('FILAMENT_MEDIA_DATE_DIRECTORIES', true),
        'format' => env('FILAMENT_MEDIA_DATE_FORMAT', 'Y/m/d'),
    ],
],
```

`FILAMENT_MEDIA_DISK` is the authoritative disk for new uploads and for registering existing physical files into `media_files`. Other allowed disks remain browsable, but cannot receive uploads or new media registrations.

Default output:

```text
uploads/2026/12/10/550e8400-e29b-41d4-a716-446655440000.jpg
```

To produce exactly a date-rooted layout:

```dotenv
FILAMENT_MEDIA_UPLOAD_PATH=
```

Result:

```text
2026/12/10/550e8400-e29b-41d4-a716-446655440000.jpg
```

To disable date directories:

```dotenv
FILAMENT_MEDIA_DATE_DIRECTORIES=false
```

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
