<?php

namespace Sarmadict\FilamentMedia\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Sarmadict\FilamentMedia\Models\MediaFile;
use Sarmadict\FilamentMedia\Support\Disk;
use Throwable;

class FileUploader
{
    public function __construct(
        private readonly MediaRegistrar $mediaRegistrar,
        private readonly UploadPathResolver $uploadPathResolver,
    ) {
    }

    public function upload(UploadedFile $file, ?string $disk = null, ?string $directory = null): MediaFile
    {
        $disk ??= Disk::default();
        $directory = $this->uploadPathResolver->resolve($directory);
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $fileName = Str::uuid()->toString().($extension !== '' ? ".{$extension}" : '');
        $visibility = $this->visibility($disk);

        $path = Storage::disk($disk)->putFileAs(
            $directory,
            $file,
            $fileName,
            ['visibility' => $visibility],
        );

        if (! is_string($path) || $path === '') {
            throw new RuntimeException('The file could not be stored.');
        }

        try {
            return $this->mediaRegistrar->register(
                $disk,
                $path,
                $file->getClientOriginalName(),
            );
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($path);

            throw $exception;
        }
    }

    private function visibility(string $disk): string
    {
        $configured = config('filament-media.upload.visibility');

        if (is_string($configured) && in_array($configured, ['public', 'private'], true)) {
            return $configured;
        }

        return (config("filesystems.disks.{$disk}.visibility") ?? null) === 'public'
            ? 'public'
            : 'private';
    }
}
