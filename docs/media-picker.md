# MediaPicker

`MediaPicker` is a Filament form field whose state can be a `media_files.id` value or a disk-relative file path. Media ID is the default for backward compatibility.

```php
use Sarmadict\FilamentMedia\Filament\Forms\Components\MediaPicker;

MediaPicker::make('cover_media_id')
    ->label('Cover');
```

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

The path is relative to its Laravel filesystem disk; it is not an absolute path or public URL. Specifying `disk()` is recommended for path-backed fields because paths are only unique within a disk. Without it, the configured default disk is checked first and the remaining allowed disks are checked in configuration order when an existing value is loaded.

Use a nullable string column with room for the package's 700-character `media_files.path` value (for example, `$table->string('cover_path', 700)->nullable()`). Path-backed fields are not foreign keys and must not be listed under `usage.direct_references`. They also do not receive automatic updates when a registered file is moved or its directory is renamed. Prefer the default ID result when the application needs a stable relationship to the media record.

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

The picker only returns active registered media. Uploads initiated inside the picker use the package-configured disk/path behavior and are registered immediately.

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
