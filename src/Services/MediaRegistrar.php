<?php

namespace Sarmadict\FilamentMedia\Services;

use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Sarmadict\FilamentMedia\Contracts\MediaRepository;
use Sarmadict\FilamentMedia\Models\MediaFile;
use Sarmadict\FilamentMedia\Support\Disk;

class MediaRegistrar
{
    public function __construct(
        private readonly MediaRepository $mediaRepository,
        private readonly MediaMetadataReader $metadataReader,
    ) {
    }

    public function register(string $disk, string $path, ?string $originalName = null): MediaFile
    {
        if ($disk !== Disk::upload()) {
            throw new RuntimeException('Media files can only be registered from the configured media disk.');
        }

        $filesystem = Storage::disk($disk);

        if (! $filesystem->exists($path)) {
            throw new RuntimeException('The file does not exist on the selected filesystem disk.');
        }

        $metadata = $this->metadataReader->read($disk, $path);
        $media = $this->mediaRepository->findByLocation($disk, $path, withTrashed: true)
            ?? $this->mediaRepository->newRecord();

        if ($media->trashed()) {
            $media->restore();
        }

        $media->forceFill([
            'disk' => $disk,
            'path' => $path,
            'original_name' => $originalName ?: ($media->original_name ?: $metadata['file_name']),
            'file_name' => $metadata['file_name'],
            'mime_type' => $metadata['mime_type'],
            'extension' => $metadata['extension'],
            'size_bytes' => $metadata['size_bytes'],
            'width' => $metadata['width'],
            'height' => $metadata['height'],
            'state' => true,
        ])->save();

        return $media->refresh();
    }
}
