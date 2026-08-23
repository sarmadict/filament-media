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
            $model = new $modelClass();
            $query = $modelClass::query();

            if (in_array(SoftDeletes::class, class_uses_recursive($model), true)) {
                $query->withTrashed();
            }

            $count = $query->where($column, $media->getKey())->count();

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
