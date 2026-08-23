<?php

namespace Sarmadict\FilamentMedia\Support;

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
        $configured = (string) config('filament-media.upload.disk', '');
        $filesystemDefault = (string) config('filesystems.default', 'local');

        if ($configured !== '' && in_array($configured, $disks, true)) {
            return $configured;
        }

        if (in_array($filesystemDefault, $disks, true)) {
            return $filesystemDefault;
        }

        return $disks[0] ?? $configured ?: $filesystemDefault;
    }
}
