# Deletion protection and usage resolution

Deleting a registered media file first calls `MediaUsageResolver`.

The default `ConfigurableMediaUsageResolver` checks:

1. polymorphic `media_attachments`, when enabled;
2. every direct reference configured under `usage.direct_references` (numeric media IDs by default).

Example:

```php
'usage' => [
    'resolver' => ConfigurableMediaUsageResolver::class,
    'check_attachments' => true,
    'direct_references' => [
        [
            'model' => App\Models\Post::class,
            'column' => 'cover_media_id',
            'label' => 'Posts',
        ],
        [
            'model' => App\Models\Course::class,
            'column' => 'thumbnail_media_id',
            'label' => 'Courses',
        ],
    ],
],
```

If usage exists, `MediaInUseException` is raised and the Filament UI shows the usage counts instead of deleting the file.

For fields that store UUIDs, filenames, or complete paths, set the matching `media_attribute`:

```php
[
    'model' => App\Models\Post::class,
    'column' => 'cover_uuid',
    'media_attribute' => 'uuid',
    'label' => 'Posts',
],
[
    'model' => App\Models\Document::class,
    'column' => 'file_path',
    'media_attribute' => 'path',
    'disk_column' => 'file_disk', // Optional host column containing the disk name.
    'label' => 'Documents',
],
```

Supported attributes are `id` (default), `media_id`, `uuid`, `file_name`, `path`, and `full_path`. `media_id` uses the numeric ID and `full_path` uses the disk-relative path. `disk_column` scopes the usage check to the matching disk; omit it for references that do not store a disk column.

## Custom resolver

Implement:

```php
use Sarmadict\FilamentMedia\Contracts\MediaUsageResolver;
use Sarmadict\FilamentMedia\Models\MediaFile;

class MyUsageResolver implements MediaUsageResolver
{
    public function usages(MediaFile $media): array
    {
        return [
            ['label' => 'External records', 'count' => 3],
        ];
    }
}
```

Then set:

```php
'usage' => [
    'resolver' => MyUsageResolver::class,
],
```
