<?php

namespace Sarmadict\FilamentMedia\Services;

use DateTimeInterface;
use InvalidArgumentException;
use Sarmadict\FilamentMedia\Support\Path;

class UploadPathResolver
{
    public function resolve(?string $basePath = null, ?DateTimeInterface $date = null): string
    {
        $basePath ??= (string) config('filament-media.upload.path', 'media');
        $basePath = Path::normalize($basePath);

        if (! (bool) config('filament-media.upload.date_directories.enabled', true)) {
            return $basePath;
        }

        $format = trim((string) config('filament-media.upload.date_directories.format', 'Y/m/d'));

        if ($format === '') {
            throw new InvalidArgumentException('filament-media.upload.date_directories.format cannot be empty.');
        }

        $date ??= now();
        $datePath = Path::normalize($date->format($format));

        if ($basePath === $datePath || ($basePath !== '' && str_ends_with($basePath, '/'.$datePath))) {
            return $basePath;
        }

        return Path::join($basePath, $datePath);
    }
}
