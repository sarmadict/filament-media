# Models and attachments

The package provides:

- `Sarmadict\FilamentMedia\Models\MediaFile`
- `Sarmadict\FilamentMedia\Models\MediaAttachment`
- `Sarmadict\FilamentMedia\Concerns\HasMediaAttachments`

## MediaFile

The default model uses soft deletes and casts `metadata` to an array and `state` to boolean. It tracks `created_by` and `updated_by` from the current authenticated user when available.

Every row has a unique, non-null `uuid` generated as a time-ordered UUIDv7, in addition to its auto-incrementing numeric `id`. The numeric primary key and attachment foreign keys remain unchanged. UUID filename mode uses the same UUID in `{uuid}.{extension}`; hash filename mode still assigns a registry UUID.

The upgrade migration backfills UUIDs for existing records, including soft-deleted media, without renaming existing physical files. Re-registering or restoring a registered file preserves its UUID.

## Custom media model

```php
namespace App\Models;

use Sarmadict\FilamentMedia\Models\MediaFile as BaseMediaFile;

class MediaFile extends BaseMediaFile
{
    // Host-specific relationships, policies, accessors, etc.
}
```

Then configure:

```php
'models' => [
    'media_file' => App\Models\MediaFile::class,
    'media_attachment' => App\Models\MediaAttachment::class,
    'user' => App\Models\User::class,
],
```

Custom classes must extend the corresponding package model because package contracts type their results against the package base model.

## Polymorphic attachments

Add the trait to any Eloquent model:

```php
use Sarmadict\FilamentMedia\Concerns\HasMediaAttachments;

class User extends Authenticatable
{
    use HasMediaAttachments;
}
```

It exposes:

```php
$model->mediaAttachments();
$model->mediaFiles();
```

The pivot supports `collection`, `sort_order`, `state`, and `created_by`.

For a collection-specific relation:

```php
public function avatarMedia(): MorphToMany
{
    return $this->morphToMany(
        config('filament-media.models.media_file'),
        'attachable',
        config('filament-media.tables.media_attachments'),
    )
        ->wherePivot('collection', 'avatar')
        ->withPivotValue('collection', 'avatar')
        ->withPivot(['id', 'sort_order', 'state', 'created_by'])
        ->withTimestamps();
}
```
