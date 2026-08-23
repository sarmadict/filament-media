<?php

namespace Sarmadict\FilamentMedia\Contracts;

use Sarmadict\FilamentMedia\Models\MediaFile;

interface PreviewUrlResolver
{
    public function forMedia(MediaFile $media): ?string;

    public function forPath(string $disk, string $path): ?string;
}
