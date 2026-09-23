---
name: filament-media-development
description: Integrate and customize sarmadict/filament-media in Laravel 12 or 13 applications using Filament 5. Use when installing the package, registering its panel plugin, adding MediaPicker fields or media relationships, configuring storage and authorization, protecting referenced media from deletion, or extending its repository and resolver contracts.
---

# Filament Media Development

## Package requirements

Use this skill for `sarmadict/filament-media`, which targets PHP 8.3+, Laravel 12 or 13, Filament 5, and Livewire 4.

The filesystem is authoritative for physical files and directories. The `media_files` table is a registry that gives application records stable media IDs and metadata. A physical file may exist without a registry row; it must be registered before `MediaPicker` can select it.

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

`MediaPicker` stores one active, registered `media_files.id` value. Back its field with a nullable foreign key when the selection is optional.

```php
use Sarmadict\FilamentMedia\Filament\Forms\Components\MediaPicker;

MediaPicker::make('cover_media_id')
    ->label('Cover')
    ->images();
```

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
FILAMENT_MEDIA_VISIBILITY=public
FILAMENT_MEDIA_DATE_DIRECTORIES=true
FILAMENT_MEDIA_DATE_FORMAT=Y/m/d
```

By default, uploads use a UUID physical filename below `uploads/Y/m/d`; the client filename is retained in `media_files.original_name`. An empty `FILAMENT_MEDIA_UPLOAD_PATH` stores files directly below the date path. Set `FILAMENT_MEDIA_DATE_DIRECTORIES=false` to disable date directories.

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

Do this for every application table that directly stores a media ID. When usage exists, deletion raises `MediaInUseException` rather than removing the file.

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
