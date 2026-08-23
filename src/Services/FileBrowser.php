<?php

namespace Sarmadict\FilamentMedia\Services;

use Sarmadict\FilamentMedia\Contracts\MediaRepository;
use Sarmadict\FilamentMedia\Contracts\PreviewUrlResolver;
use Sarmadict\FilamentMedia\Data\FileEntry;
use Sarmadict\FilamentMedia\Support\FileType;
use Sarmadict\FilamentMedia\Support\Path;
use Illuminate\Support\Facades\Storage;
use Throwable;

class FileBrowser
{
    public function __construct(
        private readonly MediaRepository $mediaRepository,
        private readonly PreviewUrlResolver $previewUrlResolver,
    ) {
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, per_page: int, last_page: int}
     */
    public function browse(
        string $disk,
        string $path = '',
        string $search = '',
        string $type = 'all',
        int $page = 1,
        int $perPage = 30,
    ): array {
        $path = Path::normalize($path);
        $filesystem = Storage::disk($disk);
        $search = mb_strtolower(trim($search));

        $directories = collect($filesystem->directories($path))
            ->filter(fn (string $directory): bool => $search === '' || str_contains(mb_strtolower(basename($directory)), $search))
            ->map(fn (string $directory): array => ['path' => $directory, 'directory' => true]);

        $files = collect($filesystem->files($path))
            ->filter(fn (string $file): bool => $search === '' || str_contains(mb_strtolower(basename($file)), $search))
            ->filter(fn (string $file): bool => FileType::matchesCategory($file, $type))
            ->map(fn (string $file): array => ['path' => $file, 'directory' => false]);

        $rawEntries = $directories
            ->sortBy(fn (array $entry): string => mb_strtolower(basename($entry['path'])))
            ->values()
            ->concat($files->sortBy(fn (array $entry): string => mb_strtolower(basename($entry['path'])))->values())
            ->values();

        $total = $rawEntries->count();
        $perPage = max(1, min(100, $perPage));
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $pageEntries = $rawEntries->slice(($page - 1) * $perPage, $perPage)->values();

        $filePaths = $pageEntries
            ->where('directory', false)
            ->pluck('path')
            ->values()
            ->all();

        $registered = $this->mediaRepository->findByDiskAndPaths($disk, $filePaths);

        $items = $pageEntries->map(function (array $rawEntry) use ($disk, $filesystem, $registered): array {
            $entryPath = $rawEntry['path'];
            $directory = $rawEntry['directory'];
            $media = $directory ? null : $registered->get($entryPath);
            $sizeBytes = null;
            $lastModified = null;
            $mimeType = null;
            $extension = $directory ? null : FileType::extension($entryPath);
            $previewUrl = null;

            if (! $directory) {
                $mimeType = $media?->mime_type;
                $sizeBytes = $media?->size_bytes !== null ? (int) $media->size_bytes : null;

                if ($mimeType === null) {
                    try {
                        $mimeType = $filesystem->mimeType($entryPath) ?: null;
                    } catch (Throwable) {
                        // Metadata is optional in filesystem browsing.
                    }
                }

                if ($sizeBytes === null) {
                    try {
                        $sizeBytes = (int) $filesystem->size($entryPath);
                    } catch (Throwable) {
                        // Metadata is optional in filesystem browsing.
                    }
                }

                $category = FileType::categoryForPath($entryPath);

                if (in_array($category, ['images', 'videos', 'audio'], true) || FileType::isImageMime($mimeType) || FileType::isVideoMime($mimeType) || FileType::isAudioMime($mimeType)) {
                    $previewUrl = $this->previewUrlResolver->forPath($disk, $entryPath);
                }
            }

            try {
                $lastModified = (int) $filesystem->lastModified($entryPath);
            } catch (Throwable) {
                // Some remote adapters may not expose this metadata.
            }

            return (new FileEntry(
                disk: $disk,
                path: $entryPath,
                name: basename($entryPath),
                directory: $directory,
                sizeBytes: $sizeBytes,
                lastModified: $lastModified,
                mimeType: $mimeType,
                extension: $extension,
                previewUrl: $previewUrl,
                media: $media,
            ))->toArray();
        })->all();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => $lastPage,
        ];
    }
}
