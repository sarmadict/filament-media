<?php

namespace Sarmadict\FilamentMedia\Services;

use Sarmadict\FilamentMedia\Contracts\MediaRepository;
use Sarmadict\FilamentMedia\Contracts\MediaUsageResolver;
use Sarmadict\FilamentMedia\Exceptions\DirectoryNotEmptyException;
use Sarmadict\FilamentMedia\Exceptions\MediaInUseException;
use Sarmadict\FilamentMedia\Support\Path;
use Illuminate\Support\Facades\Storage;

class FileDeleter
{
    public function __construct(
        private readonly MediaRepository $mediaRepository,
        private readonly MediaUsageResolver $mediaUsageResolver,
    ) {
    }

    public function deleteFile(string $disk, string $path): void
    {
        $path = Path::normalize($path);
        $media = $this->mediaRepository->findByLocation($disk, $path);

        if ($media !== null) {
            $usages = $this->mediaUsageResolver->usages($media);

            if ($usages !== []) {
                throw new MediaInUseException($usages);
            }
        }

        Storage::disk($disk)->delete($path);
        $media?->delete();
    }

    public function deleteDirectory(string $disk, string $path): void
    {
        $path = Path::normalize($path);

        if ($path === '') {
            throw new DirectoryNotEmptyException('The disk root cannot be deleted.');
        }

        $filesystem = Storage::disk($disk);

        if ($filesystem->files($path) !== [] || $filesystem->directories($path) !== []) {
            throw new DirectoryNotEmptyException('Only empty directories can be removed.');
        }

        $filesystem->deleteDirectory($path);
    }
}
