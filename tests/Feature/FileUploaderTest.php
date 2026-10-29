<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Sarmadict\FilamentMedia\Contracts\MediaRepository;
use Sarmadict\FilamentMedia\Models\MediaFile;
use Sarmadict\FilamentMedia\Services\FileUploader;
use Sarmadict\FilamentMedia\Services\MediaRegistrar;

uses(RefreshDatabase::class);

it('combines each upload hierarchy with each filename type', function (string $pathType, string $filenameType): void {
    $this->travelTo(now()->setDate(2026, 10, 12));
    Storage::fake('public');
    config()->set('filament-media.upload.path', 'assets');
    config()->set('filament-media.upload.path_type', $pathType);
    config()->set('filament-media.upload.filename_type', $filenameType);

    $media = app(FileUploader::class)->upload(UploadedFile::fake()->createWithContent('Notes.TXT', 'Example upload.'));

    $directory = match ($pathType) {
        'date' => 'assets/2026/10/12',
        'uuid_prefix' => 'assets/'.substr($media->uuid, 0, 2),
        'flat' => 'assets',
    };

    expect(Str::isUuid($media->uuid, 7))->toBeTrue()
        ->and($media->original_name)->toBe('Notes.TXT')
        ->and($media->extension)->toBe('txt')
        ->and($media->path)->toBe($directory.'/'.$media->file_name);

    if ($filenameType === 'uuid') {
        expect($media->file_name)->toBe($media->uuid.'.txt');
    } else {
        expect($media->file_name)->toMatch('/^[a-zA-Z0-9]{40}\.txt$/')
            ->and($media->file_name)->not->toBe($media->uuid.'.txt');
    }

    Storage::disk('public')->assertExists($media->path);
})->with(['date', 'uuid_prefix', 'flat'])->with(['uuid', 'hash']);

it('overrides global upload settings without affecting subsequent uploads', function (): void {
    $this->travelTo(now()->setDate(2026, 10, 12));
    Storage::fake('public');
    config()->set('filament-media.upload.path_type', 'uuid_prefix');
    config()->set('filament-media.upload.filename_type', 'hash');

    $uploader = app(FileUploader::class);
    $custom = $uploader->upload(
        UploadedFile::fake()->createWithContent('cover.txt', 'Cover.'),
        directory: 'posts/covers',
        pathType: 'date',
        filenameType: 'uuid',
        dateFormat: 'Y/m',
    );
    $default = $uploader->upload(UploadedFile::fake()->createWithContent('notes.txt', 'Notes.'));
    $root = $uploader->upload(
        UploadedFile::fake()->createWithContent('root.txt', 'Root.'),
        directory: '',
        pathType: 'flat',
    );

    expect($custom->path)->toBe('posts/covers/2026/10/'.$custom->uuid.'.txt')
        ->and($default->path)->toBe('uploads/'.substr($default->uuid, 0, 2).'/'.$default->file_name)
        ->and($default->file_name)->toMatch('/^[a-zA-Z0-9]{40}\.txt$/')
        ->and($root->path)->toBe($root->file_name);
});

it('assigns time ordered uuids to registered files and preserves them on re-registration', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('legacy/first.txt', 'First.');
    Storage::disk('public')->put('legacy/second.txt', 'Second.');

    $registrar = app(MediaRegistrar::class);
    $first = $registrar->register('public', 'legacy/first.txt');
    $second = $registrar->register('public', 'legacy/second.txt');
    $first->delete();
    $restored = $registrar->register('public', 'legacy/first.txt');

    expect(Str::isUuid($first->uuid, 7))->toBeTrue()
        ->and($first->uuid < $second->uuid)->toBeTrue()
        ->and($restored->uuid)->toBe($first->uuid)
        ->and($restored->getKey())->toBe($first->getKey())
        ->and($restored->trashed())->toBeFalse()
        ->and($restored->file_name)->toBe('first.txt');
});

it('removes the physical upload if registration fails', function (): void {
    Storage::fake('public');
    $repository = Mockery::mock(MediaRepository::class);
    $repository->shouldReceive('findByLocation')->once()->andThrow(new RuntimeException('Registration failed.'));
    app()->instance(MediaRepository::class, $repository);

    expect(fn () => app(FileUploader::class)->upload(UploadedFile::fake()->createWithContent('notes.txt', 'Notes.')))
        ->toThrow(RuntimeException::class, 'Registration failed.');

    expect(Storage::disk('public')->allFiles())->toBe([])
        ->and(MediaFile::query()->count())->toBe(0);
});

it('rejects invalid upload settings before writing files', function (array $settings): void {
    Storage::fake('public');

    expect(fn () => app(FileUploader::class)->upload(
        UploadedFile::fake()->createWithContent('notes.txt', 'Notes.'),
        ...$settings,
    ))->toThrow(InvalidArgumentException::class);

    expect(Storage::disk('public')->allFiles())->toBe([]);
})->with([
    [['pathType' => 'unknown']],
    [['filenameType' => 'original']],
    [['directory' => '../outside']],
    [['pathType' => 'date', 'dateFormat' => '']],
]);

it('deletes a Livewire temporary upload and its JSON only after the media is persisted', function (): void {
    Storage::fake('public');
    $storage = FileUploadConfiguration::storage();
    $path = FileUploadConfiguration::storeTemporaryFile(
        UploadedFile::fake()->createWithContent('notes.txt', 'Notes.'),
        FileUploadConfiguration::disk(),
    );
    $temporaryFile = TemporaryUploadedFile::createFromLivewire(basename($path));
    $storage->assertExists([$path, $path.'.json']);
    MediaFile::created(function () use ($storage, $path): void {
        $storage->assertExists([$path, $path.'.json']);
    });

    $media = app(FileUploader::class)->upload($temporaryFile);

    expect($media->exists)->toBeTrue()
        ->and($media->original_name)->toBe('notes.txt');
    Storage::disk('public')->assertExists($media->path);
    $storage->assertMissing([$path, $path.'.json']);
});

it('removes the temporary upload and JSON when registration fails', function (): void {
    Storage::fake('public');
    $storage = FileUploadConfiguration::storage();
    $path = FileUploadConfiguration::storeTemporaryFile(
        UploadedFile::fake()->createWithContent('notes.txt', 'Notes.'),
        FileUploadConfiguration::disk(),
    );
    $temporaryFile = TemporaryUploadedFile::createFromLivewire(basename($path));
    $repository = Mockery::mock(MediaRepository::class);
    $repository->shouldReceive('findByLocation')->once()->andThrow(new RuntimeException('Registration failed.'));
    app()->instance(MediaRepository::class, $repository);

    expect(fn () => app(FileUploader::class)->upload($temporaryFile))
        ->toThrow(RuntimeException::class, 'Registration failed.');

    $storage->assertMissing([$path, $path.'.json']);
    expect(Storage::disk('public')->allFiles())->toBe([])
        ->and(MediaFile::query()->count())->toBe(0);
});

it('removes temporary uploads rejected before permanent storage', function (): void {
    Storage::fake('public');
    $storage = FileUploadConfiguration::storage();
    $path = FileUploadConfiguration::storeTemporaryFile(
        UploadedFile::fake()->createWithContent('notes.txt', 'Notes.'),
        FileUploadConfiguration::disk(),
    );
    $temporaryFile = TemporaryUploadedFile::createFromLivewire(basename($path));

    expect(fn () => app(FileUploader::class)->upload($temporaryFile, pathType: 'invalid'))
        ->toThrow(InvalidArgumentException::class);

    $storage->assertMissing([$path, $path.'.json']);
    expect(Storage::disk('public')->allFiles())->toBe([])
        ->and(MediaFile::query()->count())->toBe(0);
});
