# MediaPicker

`MediaPicker` is a Filament form field whose state is a single `media_files.id` value.

```php
use Sarmadict\FilamentMedia\Filament\Forms\Components\MediaPicker;

MediaPicker::make('cover_media_id')
    ->label('Cover');
```

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

## Database relationship

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
