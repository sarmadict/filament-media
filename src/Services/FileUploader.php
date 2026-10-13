<?php

namespace Sarmadict\FilamentMedia\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Sarmadict\FilamentMedia\Models\MediaFile;
use Sarmadict\FilamentMedia\Support\Disk;
use Throwable;

class FileUploader
{
    public const FILENAME_UUID = 'uuid';

    public const FILENAME_HASH = 'hash';

    public function __construct(
        private readonly MediaRegistrar $mediaRegistrar,
        private readonly UploadPathResolver $uploadPathResolver,
    ) {}

    public function upload(
        UploadedFile $file,
        ?string $disk = null,
        ?string $directory = null,
        ?string $pathType = null,
        ?string $filenameType = null,
        ?string $dateFormat = null,
    ): MediaFile {
        $uploadDisk = Disk::upload();

        if ($disk !== null && $disk !== $uploadDisk) {
            throw new RuntimeException('Uploads are only allowed on the configured media disk.');
        }

        $disk = $uploadDisk;
        $uuid = Str::uuid7()->toString();
        $directory = $this->uploadPathResolver->resolve(
            basePath: $directory,
            uuid: $uuid,
            pathType: $pathType,
            dateFormat: $dateFormat,
        );
        $filenameType ??= (string) config('filament-media.upload.filename_type', self::FILENAME_UUID);

        if (! in_array($filenameType, [self::FILENAME_UUID, self::FILENAME_HASH], true)) {
            throw new InvalidArgumentException('Upload filename type must be uuid or hash.');
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());
        $name = $filenameType === self::FILENAME_UUID ? $uuid : Str::random(40);
        $fileName = $name.($extension !== '' ? ".{$extension}" : '');
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
                $uuid,
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
