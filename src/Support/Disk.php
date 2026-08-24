<?php

namespace Sarmadict\FilamentMedia\Support;

use RuntimeException;

final class Disk
{
    /** @return list<string> */
    public static function all(): array
    {
        $configured = array_values(array_map(
            static fn (int|string $disk): string => (string) $disk,
            array_keys((array) config('filesystems.disks', [])),
        ));

        $allowed = array_values(array_filter(array_map(
            static fn (mixed $disk): string => trim((string) $disk),
            (array) config('filament-media.disks.allowed', []),
        )));

        if ($allowed === []) {
            return $configured;
        }

        return array_values(array_intersect($configured, $allowed));
    }

    public static function default(): string
    {
        $disks = self::all();
        $mediaDisk = self::upload();

        if (in_array($mediaDisk, $disks, true)) {
            return $mediaDisk;
        }

        return $disks[0] ?? $mediaDisk;
    }

    public static function upload(): string
    {
        $disk = trim((string) config('filament-media.upload.disk', 'public'));

        if ($disk === '' || ! array_key_exists($disk, (array) config('filesystems.disks', []))) {
            throw new RuntimeException('filament-media.upload.disk must reference a configured filesystem disk.');
        }

        return $disk;
    }
}
