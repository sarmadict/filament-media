# Uploads and filesystem behavior

## Date-based storage

By default, `FileUploader` writes below a date directory using `Y/m/d`. Set `upload.path_type` to `date`, `uuid_prefix`, or `flat` to choose the hierarchy independently of the filename.

With the default base path `uploads` on 10 December 2026:

```text
uploads/2026/12/10/<uuid>.<extension>
```

With an empty base path:

```text
2026/12/10/<uuid>.<extension>
```

The base path and date format are normalized by `Support\Path`; `.` and `..` path segments are rejected.

## Filenames

Every registry row has a unique, time-ordered UUIDv7, alongside its existing numeric ID. With `upload.filename_type=uuid` (the default), the physical filename is exactly `{media_files.uuid}.{extension}`. With `hash`, the name is a random 40-character alphanumeric string plus extension, not a content checksum. The client filename is stored separately in `media_files.original_name`. Extensionless uploads omit the dot.

## UUID-prefix and custom storage

`upload.path_type=uuid_prefix` writes `{base}/{uuid first 2 characters}/{filename}`. The prefix comes from the registry UUID in both UUID and hash filename modes. `flat` writes directly into the configured base path.

The UUIDv7 prefix contains timestamp bits, so the first two characters group uploads by time rather than distributing simultaneous uploads evenly.

For a single service upload, override settings with named arguments:

```php
app(\Sarmadict\FilamentMedia\Services\FileUploader::class)->upload(
    $file,
    directory: 'posts/covers',
    pathType: 'uuid_prefix',
    filenameType: 'uuid',
);
```

For an exact custom path, pass `directory: 'profiles/avatars', pathType: 'flat'`. An empty directory explicitly selects the disk root. A `dateFormat` argument overrides the global PHP date format for a date-layout upload. Omitted options inherit configuration without modifying it. `MediaPicker` exposes equivalent per-field options; see [media-picker.md](media-picker.md).

## Upload disk and browser uploads

Uploads are only written to the disk configured by `FILAMENT_MEDIA_DISK` (`filament-media.upload.disk`). Administrators can browse every disk allowed by the package, but upload controls are available only while the configured media disk is selected.

The standalone browser uses global upload settings; the currently browsed folder does not override them. Media-picker uploads use field overrides with global fallback. For date layouts, `UploadPathResolver` does not append the same current date directory twice when a caller explicitly supplies a base path that already ends with that date path.

## Temporary uploads and cleanup

Livewire temporarily stores uploads and their JSON metadata before the package saves the permanent file and registers it. `FileUploader` deletes both temporary files after each upload attempt, including failed persistence, for picker and standalone browser uploads. The picker also removes files rejected by its MIME restrictions. Abandoned uploads and any files left behind by storage cleanup failures can be purged with the command below.

Purge existing abandoned uploads with:

```bash
php artisan filament-media:purge-temporary-uploads --dry-run
php artisan filament-media:purge-temporary-uploads
```

The command uses `livewire.temporary_file_upload.disk` (falling back to the default filesystem disk) and `livewire.temporary_file_upload.directory` (default `livewire-tmp`). It removes files older than 24 hours, including orphaned JSON metadata, and preserves an upload/metadata pair if either file is recent. `--hours=48` changes the minimum age to 48 hours; the value must be a positive integer. Permanent media outside the temporary directory is untouched.

The temporary directory is shared with other Livewire components, so this command also purges their expired temporary uploads. It can be scheduled in the host application:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('filament-media:purge-temporary-uploads')->daily();
```

## Visibility

Set `FILAMENT_MEDIA_VISIBILITY=public` or `private` to force upload visibility. When it is not set, the package uses the selected Laravel disk's configured visibility and defaults to private if no public visibility is declared.

## Preview URLs

`DefaultPreviewUrlResolver`:

- uses `Storage::url()` for disks configured as public;
- attempts a 15-minute `temporaryUrl()` for non-public disks;
- returns `null` when the adapter cannot provide a preview URL.

Replace `preview_url_resolver` when your storage provider uses a custom CDN, proxy, or signed URL scheme.

## Registry vs physical storage

A file can physically exist without a `media_files` row. On the configured media disk, the browser marks it as unregistered and provides a Register action. Registration reads available filesystem metadata and creates or restores the corresponding database row. Files on other browsable disks cannot be registered as new `media_files` records.

Registration assigns a UUID to new records and preserves it when re-registering or restoring a file. Upgrading adds UUIDs to existing records, including soft-deleted rows, without renaming files or changing numeric IDs. Run `php artisan migrate` before using the new version.

Deleting a registered file removes the physical object and soft-deletes the database row after usage checks pass.
