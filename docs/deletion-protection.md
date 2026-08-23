# Deletion protection and usage resolution

Deleting a registered media file first calls `MediaUsageResolver`.

The default `ConfigurableMediaUsageResolver` checks:

1. polymorphic `media_attachments`, when enabled;
2. every direct foreign-key reference configured under `usage.direct_references`.

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
