<?php

namespace Sarmadict\FilamentMedia\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use RuntimeException;
use Sarmadict\FilamentMedia\Contracts\MediaRepository;
use Sarmadict\FilamentMedia\Models\MediaFile;

class EloquentMediaRepository implements MediaRepository
{
    public function findByDiskAndPaths(string $disk, array $paths, bool $withTrashed = false): Collection
    {
        if ($paths === []) {
            return collect();
        }

        $query = $this->query();

        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query
            ->where('disk', $disk)
            ->whereIn('path', $paths)
            ->get()
            ->keyBy('path');
    }

    public function findByLocation(string $disk, string $path, bool $withTrashed = false): ?MediaFile
    {
        $query = $this->query();

        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query
            ->where('disk', $disk)
            ->where('path', $path)
            ->first();
    }

    public function findActiveById(int $id): ?MediaFile
    {
        return $this->query()
            ->whereKey($id)
            ->where('state', true)
            ->first();
    }

    public function updatePath(MediaFile $media, string $path): void
    {
        $media->forceFill(['path' => $path])->save();
    }

    public function newRecord(): MediaFile
    {
        $model = $this->modelClass();

        return new $model();
    }

    public function query(): Builder
    {
        $model = $this->modelClass();

        return $model::query();
    }

    /** @return class-string<MediaFile> */
    private function modelClass(): string
    {
        $model = config('filament-media.models.media_file', MediaFile::class);

        if (! is_string($model) || ! is_a($model, MediaFile::class, true)) {
            throw new RuntimeException('filament-media.models.media_file must extend '.MediaFile::class.'.');
        }

        return $model;
    }
}
