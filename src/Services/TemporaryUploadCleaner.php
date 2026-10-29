<?php

namespace Sarmadict\FilamentMedia\Services;

use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use RuntimeException;
use Throwable;

class TemporaryUploadCleaner
{
    public function delete(mixed $file): void
    {
        if (! $file instanceof TemporaryUploadedFile) {
            return;
        }

        try {
            $path = FileUploadConfiguration::path($file->getFilename(), false);

            if (! FileUploadConfiguration::storage()->delete([$path, $path.'.json'])) {
                throw new RuntimeException('The temporary upload could not be deleted.');
            }
        } catch (Throwable $exception) {
            // Cleanup must not hide an upload failure or turn persisted media into a failed upload.
            report($exception);
        }
    }
}
