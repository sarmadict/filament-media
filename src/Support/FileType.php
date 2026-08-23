<?php

namespace Sarmadict\FilamentMedia\Support;

final class FileType
{
    private const IMAGE_EXTENSIONS = [
        'avif', 'bmp', 'gif', 'heic', 'heif', 'jpeg', 'jpg', 'png', 'svg', 'webp',
    ];

    private const VIDEO_EXTENSIONS = [
        'avi', 'm4v', 'mkv', 'mov', 'mp4', 'mpeg', 'mpg', 'webm',
    ];

    private const AUDIO_EXTENSIONS = [
        'aac', 'flac', 'm4a', 'mp3', 'ogg', 'wav', 'wma',
    ];

    private const DOCUMENT_EXTENSIONS = [
        'csv', 'doc', 'docx', 'epub', 'md', 'ods', 'odt', 'pdf', 'ppt', 'pptx', 'rtf', 'txt', 'xls', 'xlsx',
    ];

    private const ARCHIVE_EXTENSIONS = [
        '7z', 'bz2', 'gz', 'rar', 'tar', 'tgz', 'xz', 'zip',
    ];

    public static function extension(string $path): ?string
    {
        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

        return $extension !== '' ? $extension : null;
    }

    public static function categoryForPath(string $path): string
    {
        $extension = self::extension($path);

        if ($extension === null) {
            return 'other';
        }

        return match (true) {
            in_array($extension, self::IMAGE_EXTENSIONS, true) => 'images',
            in_array($extension, self::VIDEO_EXTENSIONS, true) => 'videos',
            in_array($extension, self::AUDIO_EXTENSIONS, true) => 'audio',
            in_array($extension, self::DOCUMENT_EXTENSIONS, true) => 'documents',
            in_array($extension, self::ARCHIVE_EXTENSIONS, true) => 'archives',
            default => 'other',
        };
    }

    public static function matchesCategory(string $path, string $category): bool
    {
        return $category === 'all' || self::categoryForPath($path) === $category;
    }

    public static function isImageMime(?string $mimeType): bool
    {
        return str_starts_with((string) $mimeType, 'image/');
    }

    public static function isVideoMime(?string $mimeType): bool
    {
        return str_starts_with((string) $mimeType, 'video/');
    }

    public static function isAudioMime(?string $mimeType): bool
    {
        return str_starts_with((string) $mimeType, 'audio/');
    }

    /**
     * @param  list<string>  $acceptedMimeTypes
     */
    public static function mimeMatches(?string $mimeType, array $acceptedMimeTypes): bool
    {
        if ($acceptedMimeTypes === []) {
            return true;
        }

        $mimeType = strtolower((string) $mimeType);

        foreach ($acceptedMimeTypes as $acceptedMimeType) {
            $acceptedMimeType = strtolower($acceptedMimeType);

            if (str_ends_with($acceptedMimeType, '/*')) {
                $prefix = substr($acceptedMimeType, 0, -1);

                if (str_starts_with($mimeType, $prefix)) {
                    return true;
                }

                continue;
            }

            if ($mimeType === $acceptedMimeType) {
                return true;
            }
        }

        return false;
    }

    public static function humanSize(?int $bytes): string
    {
        if ($bytes === null) {
            return '—';
        }

        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        $units = ['KB', 'MB', 'GB', 'TB'];
        $value = $bytes / 1024;

        foreach ($units as $unit) {
            if ($value < 1024 || $unit === 'TB') {
                return number_format($value, $value >= 10 ? 1 : 2) . ' ' . $unit;
            }

            $value /= 1024;
        }

        return $bytes . ' B';
    }
}
