<?php

namespace Sarmadict\FilamentMedia\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Sarmadict\FilamentMedia\Models\MediaFile;

interface MediaRepository
{
    /**
     * @param  list<string>  $paths
     * @return Collection<string, MediaFile>
     */
    public function findByDiskAndPaths(string $disk, array $paths, bool $withTrashed = false): Collection;

    public function findByLocation(string $disk, string $path, bool $withTrashed = false): ?MediaFile;

    public function findActiveById(int $id): ?MediaFile;

    public function updatePath(MediaFile $media, string $path): void;

    public function newRecord(): MediaFile;

    /** @return Builder<MediaFile> */
    public function query(): Builder;
}
