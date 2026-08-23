<?php

namespace Sarmadict\FilamentMedia\Services;

use Sarmadict\FilamentMedia\Support\FileType;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MediaMetadataReader
{
    /**
     * @return array<string, mixed>
     */
    public function read(string $disk, string $path): array
    {
        $filesystem = Storage::disk($disk);
        $mimeType = 'application/octet-stream';
        $sizeBytes = 0;
        $width = null;
        $height = null;

        try {
            $mimeType = $filesystem->mimeType($path) ?: $mimeType;
        } catch (Throwable) {
            // Keep the safe default.
        }

        try {
            $sizeBytes = (int) $filesystem->size($path);
        } catch (Throwable) {
            // Keep zero when metadata is not available from the adapter.
        }

        if (FileType::isImageMime($mimeType)) {
            try {
                $localPath = $filesystem->path($path);
                $dimensions = @getimagesize($localPath);

                if (is_array($dimensions)) {
                    $width = isset($dimensions[0]) ? (int) $dimensions[0] : null;
                    $height = isset($dimensions[1]) ? (int) $dimensions[1] : null;
                }
            } catch (Throwable) {
                // Remote adapters do not necessarily expose a local path.
            }
        }

        return [
            'file_name' => basename($path),
            'mime_type' => $mimeType,
            'extension' => FileType::extension($path),
            'size_bytes' => $sizeBytes,
            'width' => $width,
            'height' => $height,
        ];
    }
}
