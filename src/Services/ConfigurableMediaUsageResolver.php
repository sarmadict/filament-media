<?php

namespace Sarmadict\FilamentMedia\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Sarmadict\FilamentMedia\Contracts\MediaUsageResolver;
use Sarmadict\FilamentMedia\Models\MediaFile;

class ConfigurableMediaUsageResolver implements MediaUsageResolver
{
    public function usages(MediaFile $media): array
    {
        $usages = [];

        if ((bool) config('filament-media.usage.check_attachments', true)) {
            $attachmentCount = $media->attachments()->count();

            if ($attachmentCount > 0) {
                $usages[] = [
                    'label' => __('filament-media::media-library.attachments'),
                    'count' => $attachmentCount,
                ];
            }
        }

        foreach ((array) config('filament-media.usage.direct_references', []) as $reference) {
            if (! is_array($reference)) {
                continue;
            }

            $modelClass = $reference['model'] ?? null;
            $column = $reference['column'] ?? null;
            $label = $reference['label'] ?? null;

            if (! is_string($modelClass) || ! is_a($modelClass, Model::class, true) || ! is_string($column) || $column === '') {
                continue;
            }

            /** @var Model $model */
            $model = new $modelClass;
            $query = $modelClass::query();

            if (in_array(SoftDeletes::class, class_uses_recursive($model), true)) {
                $query->withTrashed();
            }

            $attribute = $reference['media_attribute'] ?? 'id';

            if (! in_array($attribute, ['id', 'media_id', 'uuid', 'file_name', 'path', 'full_path'], true)) {
                continue;
            }

            $value = match ($attribute) {
                'id', 'media_id' => $media->getKey(),
                'full_path' => $media->path,
                default => $media->getAttribute($attribute),
            };

            $query->where($column, $value);

            if (is_string($reference['disk_column'] ?? null) && $reference['disk_column'] !== '') {
                $query->where($reference['disk_column'], $media->disk);
            }

            $count = $query->count();

            if ($count === 0) {
                continue;
            }

            $label = is_string($label) && $label !== '' ? $label : class_basename($modelClass);

            $usages[] = [
                'label' => __($label),
                'count' => $count,
            ];
        }

        return $usages;
    }
}
