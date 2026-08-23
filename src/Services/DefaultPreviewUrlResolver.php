<?php

namespace Sarmadict\FilamentMedia\Services;

use Illuminate\Support\Facades\Storage;
use Sarmadict\FilamentMedia\Contracts\PreviewUrlResolver;
use Sarmadict\FilamentMedia\Models\MediaFile;
use Throwable;

class DefaultPreviewUrlResolver implements PreviewUrlResolver
{
    public function forMedia(MediaFile $media): ?string
    {
        return $this->forPath((string) $media->disk, (string) $media->path);
    }

    public function forPath(string $disk, string $path): ?string
    {
        try {
            $filesystem = Storage::disk($disk);

            if (! $filesystem->exists($path)) {
                return null;
            }

            if ((config("filesystems.disks.{$disk}.visibility") ?? null) === 'public') {
                return $filesystem->url($path);
            }

            try {
                return $filesystem->temporaryUrl($path, now()->addMinutes(15));
            } catch (Throwable) {
                return null;
            }
        } catch (Throwable) {
            return null;
        }
    }
}
