<?php

namespace Sarmadict\FilamentMedia\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use RuntimeException;
use Throwable;

class PurgeTemporaryUploadsCommand extends Command
{
    protected $signature = 'filament-media:purge-temporary-uploads
        {--hours=24 : Only purge files older than this many hours}
        {--dry-run : Show how many files would be purged without deleting them}';

    protected $description = 'Purge old Livewire temporary uploads and their JSON metadata';

    public function handle(): int
    {
        $hours = filter_var($this->option('hours'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($hours === false) {
            $this->error('The --hours option must be a positive integer.');

            return self::INVALID;
        }

        try {
            $directory = FileUploadConfiguration::directory();

            if ($directory === '') {
                throw new RuntimeException('The temporary upload directory must not be the disk root.');
            }

            $storage = Storage::disk(config('livewire.temporary_file_upload.disk') ?: config('filesystems.default'));
            $cutoff = now()->subHours($hours)->getTimestamp();
            $count = 0;

            foreach ($storage->allFiles($directory) as $path) {
                if (! $storage->exists($path)) {
                    continue;
                }

                // Process metadata with its upload so a recent member protects the whole pair.
                if (str_ends_with($path, '.json') && $storage->exists(substr($path, 0, -5))) {
                    continue;
                }

                $paths = [$path];

                if ($storage->exists($path.'.json')) {
                    $paths[] = $path.'.json';
                }

                foreach ($paths as $candidate) {
                    if ($storage->lastModified($candidate) >= $cutoff) {
                        continue 2;
                    }
                }

                if (! $this->option('dry-run') && ! $storage->delete($paths)) {
                    throw new RuntimeException('Could not delete temporary upload: '.$path);
                }

                $count += count($paths);
            }

            $this->info(($this->option('dry-run') ? 'Would purge ' : 'Purged ').$count.' temporary file(s).');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
