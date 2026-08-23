<?php

namespace Sarmadict\FilamentMedia\Data;

use Sarmadict\FilamentMedia\Models\MediaFile;
use Sarmadict\FilamentMedia\Support\FileType;

final readonly class FileEntry
{
    public function __construct(
        public string $disk,
        public string $path,
        public string $name,
        public bool $directory,
        public ?int $sizeBytes = null,
        public ?int $lastModified = null,
        public ?string $mimeType = null,
        public ?string $extension = null,
        public ?string $previewUrl = null,
        public ?MediaFile $media = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'disk' => $this->disk,
            'path' => $this->path,
            'name' => $this->name,
            'directory' => $this->directory,
            'category' => $this->directory ? 'folder' : FileType::categoryForPath($this->path),
            'size_bytes' => $this->sizeBytes,
            'last_modified' => $this->lastModified,
            'mime_type' => $this->mimeType,
            'extension' => $this->extension,
            'preview_url' => $this->previewUrl,
            'media_id' => $this->media?->getKey(),
            'original_name' => $this->media?->original_name,
            'width' => $this->media?->width !== null ? (int) $this->media->width : null,
            'height' => $this->media?->height !== null ? (int) $this->media->height : null,
            'duration_seconds' => $this->media?->duration_seconds !== null ? (int) $this->media->duration_seconds : null,
            'registered' => $this->media !== null,
        ];
    }
}
