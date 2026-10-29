<?php

use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    config()->set('livewire.temporary_file_upload.disk', 'temporary');
    config()->set('livewire.temporary_file_upload.directory', 'custom/tmp');
    Storage::fake('temporary');
});

function temporaryUploadFile(string $path, int $ageInHours): void
{
    $storage = Storage::disk('temporary');
    $storage->put($path, 'Temporary data.');
    touch($storage->path($path), now()->subHours($ageInHours)->getTimestamp());
}

it('purges old uploads and orphaned metadata while preserving recent pairs and permanent files', function (): void {
    temporaryUploadFile('custom/tmp/old.txt', 48);
    temporaryUploadFile('custom/tmp/old.txt.json', 48);
    temporaryUploadFile('custom/tmp/orphan.txt.json', 48);
    temporaryUploadFile('custom/tmp/legacy-without-metadata.txt', 48);
    temporaryUploadFile('custom/tmp/recent.txt', 1);
    temporaryUploadFile('custom/tmp/recent.txt.json', 1);
    temporaryUploadFile('custom/tmp/recent-metadata.txt', 48);
    temporaryUploadFile('custom/tmp/recent-metadata.txt.json', 1);
    temporaryUploadFile('custom/tmp/recent-upload.txt', 1);
    temporaryUploadFile('custom/tmp/recent-upload.txt.json', 48);
    temporaryUploadFile('uploads/permanent.txt', 48);
    temporaryUploadFile('livewire-tmp/other-directory.txt', 48);

    $this->artisan('filament-media:purge-temporary-uploads')
        ->expectsOutput('Purged 4 temporary file(s).')
        ->assertSuccessful();

    Storage::disk('temporary')->assertMissing([
        'custom/tmp/old.txt', 'custom/tmp/old.txt.json',
        'custom/tmp/orphan.txt.json', 'custom/tmp/legacy-without-metadata.txt',
    ]);
    Storage::disk('temporary')->assertExists([
        'custom/tmp/recent.txt', 'custom/tmp/recent.txt.json',
        'custom/tmp/recent-metadata.txt', 'custom/tmp/recent-metadata.txt.json',
        'custom/tmp/recent-upload.txt', 'custom/tmp/recent-upload.txt.json',
        'uploads/permanent.txt', 'livewire-tmp/other-directory.txt',
    ]);
});

it('previews cleanup without deleting files', function (): void {
    temporaryUploadFile('custom/tmp/old.txt', 48);
    temporaryUploadFile('custom/tmp/old.txt.json', 48);

    $this->artisan('filament-media:purge-temporary-uploads', ['--dry-run' => true])
        ->expectsOutput('Would purge 2 temporary file(s).')
        ->assertSuccessful();

    Storage::disk('temporary')->assertExists(['custom/tmp/old.txt', 'custom/tmp/old.txt.json']);
});

it('allows a custom age threshold', function (): void {
    temporaryUploadFile('custom/tmp/old.txt', 8);
    temporaryUploadFile('custom/tmp/recent.txt', 2);

    $this->artisan('filament-media:purge-temporary-uploads', ['--hours' => 6])
        ->expectsOutput('Purged 1 temporary file(s).')
        ->assertSuccessful();

    Storage::disk('temporary')->assertMissing('custom/tmp/old.txt');
    Storage::disk('temporary')->assertExists('custom/tmp/recent.txt');
});

it('uses the default filesystem disk and Livewire directory when no overrides are configured', function (): void {
    config()->set('livewire.temporary_file_upload.disk', null);
    config()->set('livewire.temporary_file_upload.directory', null);
    config()->set('filesystems.default', 'temporary');
    temporaryUploadFile('livewire-tmp/old.txt', 48);

    $this->artisan('filament-media:purge-temporary-uploads')->assertSuccessful();

    Storage::disk('temporary')->assertMissing('livewire-tmp/old.txt');
});

it('rejects invalid age thresholds without deleting files', function (string $hours): void {
    temporaryUploadFile('custom/tmp/old.txt', 48);

    $this->artisan('filament-media:purge-temporary-uploads', ['--hours' => $hours])
        ->expectsOutput('The --hours option must be a positive integer.')
        ->assertExitCode(2);

    Storage::disk('temporary')->assertExists('custom/tmp/old.txt');
})->with(['0', '-1', 'invalid', '1.5']);

it('refuses to purge the disk root', function (): void {
    config()->set('livewire.temporary_file_upload.directory', '/');
    temporaryUploadFile('permanent.txt', 48);

    $this->artisan('filament-media:purge-temporary-uploads')
        ->expectsOutput('The temporary upload directory must not be the disk root.')
        ->assertFailed();

    Storage::disk('temporary')->assertExists('permanent.txt');
});
