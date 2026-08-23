<?php

namespace Sarmadict\FilamentMedia\Services;

use Sarmadict\FilamentMedia\Contracts\MediaRepository;
use Sarmadict\FilamentMedia\Support\Path;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class DirectoryRenamer
{
    public function __construct(
        private readonly MediaRepository $mediaRepository,
    ) {
    }

    public function rename(string $disk, string $path, string $newName): string
    {
        $path = Path::normalize($path);
        $newName = trim($newName);

        if ($path === '') {
            throw new InvalidArgumentException('The disk root cannot be renamed.');
        }

        if ($newName === '' || $newName === '.' || $newName === '..' || str_contains($newName, '/') || str_contains($newName, '\\')) {
            throw new InvalidArgumentException('The directory name is invalid.');
        }

        $filesystem = Storage::disk($disk);

        if (! $filesystem->directoryExists($path)) {
            throw new InvalidArgumentException('The directory does not exist.');
        }

        $targetPath = Path::join(Path::parent($path), $newName);

        if ($targetPath === $path) {
            return $path;
        }

        if ($filesystem->directoryExists($targetPath) || $filesystem->fileExists($targetPath)) {
            throw new InvalidArgumentException('A file or directory with this name already exists.');
        }

        $files = array_values($filesystem->allFiles($path));
        $directories = array_values($filesystem->allDirectories($path));
        $registered = $this->mediaRepository->findByDiskAndPaths($disk, $files, withTrashed: true);

        foreach ($files as $sourceFile) {
            $targetFile = $this->targetFor($sourceFile, $path, $targetPath);
            $existingMedia = $this->mediaRepository->findByLocation($disk, $targetFile, true);
            $sourceMedia = $registered->get($sourceFile);

            if ($existingMedia !== null && $existingMedia->getKey() !== $sourceMedia?->getKey()) {
                throw new InvalidArgumentException('A media record already exists for a target path.');
            }
        }

        /** @var list<array{source: string, target: string}> $moved */
        $moved = [];

        try {
            $filesystem->makeDirectory($targetPath);

            foreach ($directories as $sourceDirectory) {
                $filesystem->makeDirectory($this->targetFor($sourceDirectory, $path, $targetPath));
            }

            foreach ($files as $sourceFile) {
                $targetFile = $this->targetFor($sourceFile, $path, $targetPath);
                $parent = Path::parent($targetFile);

                if ($parent !== '') {
                    $filesystem->makeDirectory($parent);
                }

                if (! $filesystem->move($sourceFile, $targetFile)) {
                    throw new RuntimeException("Unable to move [{$sourceFile}] to [{$targetFile}].");
                }

                $moved[] = [
                    'source' => $sourceFile,
                    'target' => $targetFile,
                ];

                $media = $registered->get($sourceFile);

                if ($media !== null) {
                    $this->mediaRepository->updatePath($media, $targetFile);
                }
            }

            $filesystem->deleteDirectory($path);
        } catch (Throwable $exception) {
            $this->rollback($filesystem, $disk, $moved, $targetPath);

            throw $exception;
        }

        return $targetPath;
    }

    private function targetFor(string $source, string $oldRoot, string $newRoot): string
    {
        $relative = ltrim(substr($source, strlen($oldRoot)), '/');

        return Path::join($newRoot, $relative);
    }

    /**
     * @param  list<array{source: string, target: string}>  $moved
     */
    private function rollback(FilesystemAdapter $filesystem, string $disk, array $moved, string $targetPath): void
    {
        foreach (array_reverse($moved) as $pair) {
            try {
                if ($filesystem->fileExists($pair['target'])) {
                    $parent = Path::parent($pair['source']);

                    if ($parent !== '') {
                        $filesystem->makeDirectory($parent);
                    }

                    $filesystem->move($pair['target'], $pair['source']);
                }

                $media = $this->mediaRepository->findByLocation($disk, $pair['target'], true);

                if ($media !== null) {
                    $this->mediaRepository->updatePath($media, $pair['source']);
                }
            } catch (Throwable $rollbackException) {
                report($rollbackException);
            }
        }

        try {
            if ($filesystem->directoryExists($targetPath)) {
                $filesystem->deleteDirectory($targetPath);
            }
        } catch (Throwable $rollbackException) {
            report($rollbackException);
        }
    }
}
