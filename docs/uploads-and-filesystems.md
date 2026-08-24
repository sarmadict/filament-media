# Uploads and filesystem behavior

## Date-based storage

Every upload made through `FileUploader` is written below a date directory. The default date format is `Y/m/d`.

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

Physical filenames use UUIDs. The client filename is stored separately in `media_files.original_name`. This avoids collisions and prevents user-controlled names from determining physical storage paths.

## Upload disk and browser uploads

Uploads are only written to the disk configured by `FILAMENT_MEDIA_DISK` (`filament-media.upload.disk`). Administrators can browse every disk allowed by the package, but upload controls are available only while the configured media disk is selected.

Browser and media-picker uploads always use the configured upload base path plus the date directory; the currently browsed folder does not override the configured base path. The `UploadPathResolver` does not append the same current date directory twice when a caller explicitly supplies a base path that already ends with that date path.

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

Deleting a registered file removes the physical object and soft-deletes the database row after usage checks pass.
