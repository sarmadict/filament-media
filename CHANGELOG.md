# Changelog

## Unreleased

- Keep failed picker uploads on the Upload tab with an alert explaining each failure, and remove temporary files and JSON metadata for failed or rejected uploads.
- Added automatic picker uploads from dropped or selected files, with drag-triggered tab switching and upload progress.
- Clean up Livewire temporary uploads and JSON metadata after saving media, and add `filament-media:purge-temporary-uploads` for expired data.
- Added configurable picker modal widths globally and per field, using Filament's width scale and a full-screen option with edge margins.
- Added unique time-ordered UUIDv7 values to media records, with an upgrade migration that backfills existing and soft-deleted rows.
- Added independent date, UUID-prefix, and flat upload layouts and UUID/hash filename strategies, with per-field path and naming overrides.
- Replaced the date-directory toggle with a single `upload.path_type` setting and moved the date format to `upload.date_format`.
- Added UUID, filename, media_id, and full_path picker results and a configurable application default.
- Added configurable media attributes and disk columns for deletion checks on non-ID references.
- Updated the package development skill and documentation for all upload and picker options.
- Added per-field `MediaPicker` result types so selections can store either the media ID or its disk-relative path.

## 1.0.0 - 2026-08-17

- Extracted the media library from the host application into `sarmadict/filament-media`.
- Added Composer PSR-4 metadata and Laravel package discovery.
- Added package-owned media models, migrations, views, translations, contracts, and services.
- Added configurable media models and database table names.
- Added configurable filesystem disks and Laravel Gate-based authorization.
- Added configurable media-usage checks before deletion.
- Added configurable upload base path.
- Added automatic date directories using `Y/m/d` by default.
- Added package documentation and test scaffolding.
