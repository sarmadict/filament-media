# MediaPicker

`MediaPicker` stores an active registered media selection as a numeric ID, UUID, filename, or complete disk-relative path. Media ID remains the default.

```php
use Sarmadict\FilamentMedia\Filament\Forms\Components\MediaPicker;

MediaPicker::make('cover_media_id')
    ->label('Cover');
```

## Modal size

The picker inherits `filament-media.picker.modal_width`, which defaults to `normal` to preserve the current size. Override it per field using Filament's [modal width convention](https://filamentphp.com/docs/5.x/actions/modals#changing-the-modal-width):

```php
use Filament\Support\Enums\Width;

MediaPicker::make('cover_media_id')->modalWidth(Width::Screen);
MediaPicker::make('document_media_id')->modalWidth(Width::FiveExtraLarge);
MediaPicker::make('avatar_media_id')->modalWidth('normal');
```

`Width::Screen` (or `'screen'`) fills the viewport's width and height, leaving a 12px margin on desktop and 7px on small screens. Other supported widths are `xs`, `sm`, `md`, `lg`, `xl`, `2xl`, `3xl`, `4xl`, `5xl`, `6xl`, and `7xl`, or their corresponding `Width` enum cases. These follow Tailwind's maximum-width scale and keep the normal modal height. Smaller viewports constrain the width to fit.

`modalWidth()` also accepts a closure evaluated in the Filament field context. Passing null, calling `modalWidth()` without an argument, or returning null from a closure inherits the config default. Modal sizing does not affect inline pickers.

## Result type

Store the registered media ID (the default):

```php
MediaPicker::make('cover_media_id')
    ->resultType(MediaPicker::RESULT_ID);
```

Store the value from `media_files.path` instead:

```php
MediaPicker::make('cover_path')
    ->resultType(MediaPicker::RESULT_PATH)
    ->disk('public');
```

| Constant | Value saved on the application field |
| --- | --- |
| `RESULT_ID` / `RESULT_MEDIA_ID` | Numeric `media_files.id` |
| `RESULT_UUID` | Unique `media_files.uuid` (UUIDv7) |
| `RESULT_FILE_NAME` | `media_files.file_name`, including extension |
| `RESULT_PATH` / `RESULT_FULL_PATH` | `media_files.path`, including directories and filename |

`RESULT_MEDIA_ID` aliases the existing `RESULT_ID` behavior. `RESULT_FULL_PATH` aliases `RESULT_PATH`: the complete path is relative to the Laravel filesystem disk, not an absolute path or public URL. The `filament-media.picker.result_type` setting changes the application default; `resultType()` overrides it and may accept a closure.

For example:

```php
MediaPicker::make('cover_uuid')->resultType(MediaPicker::RESULT_UUID);
MediaPicker::make('asset_name')->resultType(MediaPicker::RESULT_FILE_NAME)->disk('public');
MediaPicker::make('document_path')->resultType(MediaPicker::RESULT_FULL_PATH)->disk('public');
```

Use a UUID/36-character string column for UUIDs, a string of up to 255 characters for filenames, and a string with room for the 700-character path value for paths. Optional selections should use nullable columns.

Specifying `disk()` is recommended for filename- and path-backed fields. Without it, paths resolve against the configured default disk first, followed by other allowed disks. Filename resolution requires exactly one active match on the field disk (or across all allowed disks when unset); ambiguous legacy filenames show no selection. Filename and path values are not updated automatically after moves or renames. Prefer IDs or UUIDs for stable references, and configure [deletion protection](deletion-protection.md) with the matching `media_attribute` for every stored reference.

## Per-field uploads

Each setting inherits the global upload configuration unless overridden. Combine different layouts and filename types within one form or application:

```php
MediaPicker::make('cover_uuid')
    ->images()
    ->resultType(MediaPicker::RESULT_UUID)
    ->uploadPath('posts/covers')
    ->uploadPathType('uuid_prefix')
    ->uploadFilenameType('uuid');

MediaPicker::make('document_path')
    ->documents()
    ->disk('public')
    ->resultType(MediaPicker::RESULT_FULL_PATH)
    ->uploadPath('documents')
    ->uploadPathType('date')
    ->uploadDateFormat('Y/m')
    ->uploadFilenameType('hash');

MediaPicker::make('avatar_media_id')
    ->images()
    ->uploadPath('profiles/avatars')
    ->uploadPathType('flat');
```

`uploadPath()` supplies the base directory. `uploadPathType()` accepts `date`, `uuid_prefix`, or `flat`; use `flat` for an exact custom directory without appended hierarchy. `uploadFilenameType()` accepts `uuid` or `hash`. `uploadDateFormat()` supplies a PHP date format, used only for `date` paths. These methods accept strings, closures evaluated in the Filament field context, or null to inherit the corresponding global setting. An empty upload path explicitly selects the disk root.

These options affect new uploads from that field, including inline pickers; selecting an existing file preserves its filename and location. Upload options and result type are independent. A field that stores UUIDs can upload hash-named files, and a field that stores IDs can upload UUID-named files. The standalone media library continues to use global settings.

## MIME presets

Images:

```php
MediaPicker::make('image_media_id')->images();
```

Video:

```php
MediaPicker::make('video_media_id')->videos();
```

Audio:

```php
MediaPicker::make('audio_media_id')->audio();
```

Common documents:

```php
MediaPicker::make('document_media_id')->documents();
```

Custom MIME rules:

```php
MediaPicker::make('asset_media_id')
    ->acceptedMimeTypes([
        'image/*',
        'application/pdf',
    ]);
```

The picker only returns active registered media. Uploads use the configured media disk and the field's upload settings with global fallback, and are registered immediately.

## Database relationship for ID results

A typical host model relation is:

```php
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Sarmadict\FilamentMedia\Models\MediaFile;

public function coverMedia(): BelongsTo
{
    return $this->belongsTo(MediaFile::class, 'cover_media_id');
}
```

If the host uses a custom model class, use that configured subclass in the relation.

For UUID results, specify the owner key:

```php
public function coverMedia(): BelongsTo
{
    return $this->belongsTo(MediaFile::class, 'cover_uuid', 'uuid');
}
```
