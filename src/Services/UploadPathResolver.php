<?php

namespace Sarmadict\FilamentMedia\Services;

use DateTimeInterface;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Sarmadict\FilamentMedia\Support\Path;

class UploadPathResolver
{
    public const PATH_DATE = 'date';

    public const PATH_UUID_PREFIX = 'uuid_prefix';

    public const PATH_FLAT = 'flat';

    public function resolve(
        ?string $basePath = null,
        ?DateTimeInterface $date = null,
        ?string $uuid = null,
        ?string $pathType = null,
        ?string $dateFormat = null,
    ): string {
        $basePath ??= (string) config('filament-media.upload.path', 'media');
        $basePath = Path::normalize($basePath);

        $pathType = $this->pathType($pathType);

        if ($pathType === self::PATH_FLAT) {
            return $basePath;
        }

        if ($pathType === self::PATH_UUID_PREFIX) {
            if ($uuid === null || ! Str::isUuid($uuid)) {
                throw new InvalidArgumentException('A valid media UUID is required for uuid_prefix upload paths.');
            }

            return Path::join($basePath, substr($uuid, 0, 2));
        }

        $format = trim($dateFormat ?? (string) config('filament-media.upload.date_format', 'Y/m/d'));

        if ($format === '') {
            throw new InvalidArgumentException('filament-media.upload.date_format cannot be empty.');
        }

        $date ??= now();
        $datePath = Path::normalize($date->format($format));

        if ($basePath === $datePath || ($basePath !== '' && str_ends_with($basePath, '/'.$datePath))) {
            return $basePath;
        }

        return Path::join($basePath, $datePath);
    }

    public function pathType(?string $pathType = null): string
    {
        $pathType ??= config('filament-media.upload.path_type', self::PATH_DATE);

        if (! in_array($pathType, [self::PATH_DATE, self::PATH_UUID_PREFIX, self::PATH_FLAT], true)) {
            throw new InvalidArgumentException('Upload path type must be date, uuid_prefix, or flat.');
        }

        return $pathType;
    }
}
