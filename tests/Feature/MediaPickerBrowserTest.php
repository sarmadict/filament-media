<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Sarmadict\FilamentMedia\Livewire\MediaPickerBrowser;
use Sarmadict\FilamentMedia\Models\MediaFile;
use Sarmadict\FilamentMedia\Services\MediaRegistrar;

uses(RefreshDatabase::class);

it('includes the media id and path in the selection payload', function (): void {
    config()->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));

    $media = new MediaFile;
    $media->forceFill([
        'disk' => 'public',
        'path' => 'uploads/covers/example.jpg',
        'original_name' => 'example.jpg',
        'file_name' => 'example.jpg',
        'mime_type' => 'image/jpeg',
        'size_bytes' => 1024,
        'state' => true,
    ])->save();

    Livewire::test(MediaPickerBrowser::class, [
        'pickerId' => 'cover-picker',
        'disk' => 'public',
    ])
        ->call('selectAndConfirm', $media->getKey())
        ->assertDispatched(
            'filament-media-selected',
            fn (string $event, array $parameters): bool => $parameters['id'] === $media->getKey()
                && $parameters['media']['id'] === $media->getKey()
                && $parameters['media']['media_id'] === $media->getKey()
                && $parameters['media']['uuid'] === $media->uuid
                && $parameters['media']['file_name'] === $media->file_name
                && $parameters['media']['path'] === $media->path
                && $parameters['media']['full_path'] === $media->path,
        );
});

it('plays selected video and audio in the sidebar and updates the preview when selection changes', function (string $mimeType, string $extension, string $tag): void {
    config()->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    config()->set('filesystems.disks.public.visibility', 'public');
    Storage::fake('public');

    $mediaFiles = collect(['first', 'second'])->map(function (string $name) use ($mimeType, $extension): MediaFile {
        $fileName = $name.'.'.$extension;
        $path = 'uploads/'.$fileName;
        Storage::disk('public')->put($path, 'Preview content.');

        $media = new MediaFile;
        $media->forceFill([
            'disk' => 'public',
            'path' => $path,
            'original_name' => $fileName,
            'file_name' => $fileName,
            'mime_type' => $mimeType,
            'size_bytes' => 1024,
            'state' => true,
        ])->save();

        return $media;
    });

    $picker = Livewire::test(MediaPickerBrowser::class, [
        'pickerId' => 'playback-picker',
        'disk' => 'public',
    ]);

    $document = new DOMDocument;
    @$document->loadHTML($picker->html());
    expect((new DOMXPath($document))->query('//video | //audio'))->toHaveCount(0);

    $previewKeys = [];

    foreach ($mediaFiles as $media) {
        $picker->call('select', $media->getKey())->assertSet('selectedId', $media->getKey());

        @$document->loadHTML($picker->html());
        $xpath = new DOMXPath($document);
        $players = $xpath->query('//aside//'.$tag);
        $player = $players->item(0);

        expect($players)->toHaveCount(1)
            ->and($xpath->query('//video | //audio'))->toHaveCount(1)
            ->and($player->getAttribute('src'))->toBe(Storage::disk('public')->url($media->path))
            ->and($player->hasAttribute('controls'))->toBeTrue()
            ->and($player->getAttribute('preload'))->toBe('metadata')
            ->and($player->hasAttribute('autoplay'))->toBeFalse();

        $previewKeys[] = $player->parentNode->getAttribute('wire:key');
    }

    expect($previewKeys[0])->not->toBe($previewKeys[1]);

    $picker->call('confirmSelection')->assertDispatched(
        'filament-media-selected',
        fn (string $event, array $parameters): bool => $parameters['media']['url'] === null,
    );
})->with([
    'video' => ['video/mp4', 'mp4', 'video'],
    'audio' => ['audio/mpeg', 'mp3', 'audio'],
]);

it('keeps the sidebar icon when selected video or audio has no preview URL', function (string $mimeType, string $extension): void {
    config()->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    Storage::fake('public');

    $media = new MediaFile;
    $media->forceFill([
        'disk' => 'public',
        'path' => 'uploads/missing.'.$extension,
        'original_name' => 'missing.'.$extension,
        'file_name' => 'missing.'.$extension,
        'mime_type' => $mimeType,
        'size_bytes' => 1024,
        'state' => true,
    ])->save();

    $picker = Livewire::test(MediaPickerBrowser::class, [
        'pickerId' => 'missing-preview-picker',
        'disk' => 'public',
    ])->call('select', $media->getKey());

    $document = new DOMDocument;
    @$document->loadHTML($picker->html());
    $xpath = new DOMXPath($document);

    expect($xpath->query('//video | //audio'))->toHaveCount(0)
        ->and($xpath->query('//aside//div[@class="filament-media-picker-details__preview"]//svg'))->toHaveCount(1);
})->with([
    'video' => ['video/mp4', 'mp4'],
    'audio' => ['audio/mpeg', 'mp3'],
]);

it('keeps field upload overrides isolated between pickers', function (): void {
    config()->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    config()->set('filament-media.authorization.enabled', false);
    config()->set('filament-media.upload.path_type', 'uuid_prefix');
    config()->set('filament-media.upload.filename_type', 'uuid');
    Storage::fake('public');

    Livewire::test(MediaPickerBrowser::class, [
        'pickerId' => 'custom-picker',
        'uploadPath' => 'posts/covers',
        'uploadPathType' => 'flat',
        'uploadFilenameType' => 'hash',
    ])->set('uploads', [UploadedFile::fake()->createWithContent('custom.txt', 'Custom.')])
        ->assertHasNoErrors();

    Livewire::test(MediaPickerBrowser::class, ['pickerId' => 'default-picker'])
        ->set('uploads', [UploadedFile::fake()->createWithContent('default.txt', 'Default.')])
        ->assertHasNoErrors();

    $custom = MediaFile::query()->where('original_name', 'custom.txt')->firstOrFail();
    $default = MediaFile::query()->where('original_name', 'default.txt')->firstOrFail();

    expect($custom->path)->toBe('posts/covers/'.$custom->file_name)
        ->and($custom->file_name)->toMatch('/^[a-zA-Z0-9]{40}\.txt$/')
        ->and($default->path)->toBe('uploads/'.substr($default->uuid, 0, 2).'/'.$default->uuid.'.txt');

    Storage::disk('public')->assertExists([$custom->path, $default->path]);
});

it('prevents clients from changing configured upload overrides', function (string $property): void {
    config()->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));

    Livewire::test(MediaPickerBrowser::class, ['pickerId' => 'locked-picker'])
        ->set($property, 'client-override');
})->with(['uploadPath', 'uploadPathType', 'uploadFilenameType', 'uploadDateFormat'])
    ->throws(CannotUpdateLockedPropertyException::class);

it('accepts drops across both tabs and starts file selections without a confirmation button', function (): void {
    config()->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    config()->set('filament-media.authorization.enabled', false);

    Storage::fake('public');

    $picker = Livewire::test(MediaPickerBrowser::class, [
        'pickerId' => 'upload-picker',
        'disk' => 'public',
    ]);

    $document = new DOMDocument;
    @$document->loadHTML($picker->html());
    $xpath = new DOMXPath($document);
    $browser = $xpath->query('//div[contains(@class, "filament-media-picker-browser")][@*[name()="x-data"]]')->item(0);
    $input = $xpath->query('//input[@type="file"]')->item(0);

    expect($browser->getAttribute('x-on:dragenter'))->toBe('dragEnter($event)')
        ->and($browser->getAttribute('x-on:drop.prevent'))->toBe('uploadFiles($event.dataTransfer.files)')
        ->and($input->getAttribute('x-on:change'))->toContain('uploadFiles($event.target.files)')
        ->and($xpath->query('//button[@*[name()="wire:click"]="storeUploads"]'))->toHaveCount(0);
});

it('stores multiple uploaded files and resets the picker for the next upload', function (): void {
    config()->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    config()->set('filament-media.authorization.enabled', false);

    Storage::fake('public');

    $picker = Livewire::test(MediaPickerBrowser::class, [
        'pickerId' => 'upload-picker',
        'disk' => 'public',
        'acceptedMimeTypes' => ['text/plain'],
    ])
        ->set('tab', 'upload')
        ->set('uploads', [
            UploadedFile::fake()->createWithContent('first.txt', 'First upload.'),
            UploadedFile::fake()->createWithContent('second.txt', 'Second upload.'),
        ])
        ->assertHasNoErrors()
        ->assertSet('uploads', [])
        ->assertSet('tab', 'library');

    $mediaFiles = MediaFile::query()->orderBy('id')->get();

    expect($mediaFiles)->toHaveCount(2)
        ->and($mediaFiles->pluck('original_name')->all())->toBe(['first.txt', 'second.txt'])
        ->and($picker->get('selectedId'))->toBe($mediaFiles->last()->getKey());

    Storage::disk('public')->assertExists($mediaFiles->pluck('path')->all());
    expect(FileUploadConfiguration::storage()->allFiles(FileUploadConfiguration::directory()))->toBe([]);

    $picker->set('tab', 'upload')->set('uploads', [
        UploadedFile::fake()->createWithContent('third.txt', 'Another upload.'),
    ])->assertHasNoErrors()->assertSet('uploads', []);

    expect(MediaFile::query()->count())->toBe(3);
});

it('automatically persists files dropped while the library tab is active', function (): void {
    config()->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    config()->set('filament-media.authorization.enabled', false);
    Storage::fake('public');

    $picker = Livewire::test(MediaPickerBrowser::class, ['pickerId' => 'drop-picker'])
        ->set('uploads', [UploadedFile::fake()->createWithContent('dropped.txt', 'Dropped file.')])
        ->assertHasNoErrors()
        ->assertSet('uploads', [])
        ->assertSet('tab', 'library');

    $media = MediaFile::query()->sole();

    expect($picker->get('selectedId'))->toBe($media->getKey());
    Storage::disk('public')->assertExists($media->path);
});

it('rejects disallowed MIME types during automatic uploads while saving allowed files', function (): void {
    config()->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    config()->set('filament-media.authorization.enabled', false);
    Storage::fake('public');

    $picker = Livewire::test(MediaPickerBrowser::class, [
        'pickerId' => 'restricted-picker',
        'acceptedMimeTypes' => ['text/plain'],
    ])->set('uploads', [
        UploadedFile::fake()->create('rejected.pdf', 1, 'application/pdf'),
        UploadedFile::fake()->createWithContent('allowed.txt', 'Allowed file.'),
    ])->assertHasErrors(['uploads'])
        ->assertSet('uploads', [])
        ->assertSet('tab', 'upload')
        ->assertSee(__('filament-media::media-library.invalid_file_type', ['name' => 'rejected.pdf']));

    $media = MediaFile::query()->sole();

    expect($media->original_name)->toBe('allowed.txt')
        ->and($picker->get('selectedId'))->toBe($media->getKey())
        ->and(FileUploadConfiguration::storage()->allFiles(FileUploadConfiguration::directory()))->toBe([]);
    Storage::disk('public')->assertExists($media->path);
});

it('shows a persistent alert and removes rejected uploads when the whole batch has mismatched types', function (): void {
    config()->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    config()->set('filament-media.authorization.enabled', false);
    Storage::fake('public');

    $picker = Livewire::test(MediaPickerBrowser::class, [
        'pickerId' => 'rejected-picker',
        'acceptedMimeTypes' => ['image/*'],
    ])->set('uploads', [UploadedFile::fake()->createWithContent('notes.txt', 'Not an image.')])
        ->assertHasErrors(['uploads'])
        ->assertSet('uploads', [])
        ->assertSet('tab', 'upload')
        ->assertSet('selectedId', null);

    $document = new DOMDocument;
    @$document->loadHTML($picker->html());
    $alerts = (new DOMXPath($document))->query('//div[@role="alert"]');

    expect($alerts)->toHaveCount(1)
        ->and($alerts->item(0)->textContent)->toContain(__('filament-media::media-library.invalid_file_type', ['name' => 'notes.txt']))
        ->and(FileUploadConfiguration::storage()->allFiles(FileUploadConfiguration::directory()))->toBe([])
        ->and(MediaFile::query()->count())->toBe(0);
});

it('keeps failed saves on the upload tab with the reason and clears errors on a successful retry', function (): void {
    config()->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    config()->set('filament-media.authorization.enabled', false);
    Storage::fake('public');
    $registrar = app(MediaRegistrar::class);
    $failingRegistrar = Mockery::mock(MediaRegistrar::class);
    $failingRegistrar->shouldReceive('register')->once()->andThrow(new RuntimeException('Media registry unavailable.'));
    app()->instance(MediaRegistrar::class, $failingRegistrar);

    $picker = Livewire::test(MediaPickerBrowser::class, ['pickerId' => 'retry-picker'])
        ->set('uploads', [UploadedFile::fake()->createWithContent('notes.txt', 'Failed upload.')])
        ->assertHasErrors(['uploads'])
        ->assertSet('tab', 'upload')
        ->assertSet('uploads', [])
        ->assertSet('selectedId', null)
        ->assertSee(__('filament-media::media-library.upload_failed_file', [
            'name' => 'notes.txt',
            'reason' => 'Media registry unavailable.',
        ]));

    expect(FileUploadConfiguration::storage()->allFiles(FileUploadConfiguration::directory()))->toBe([])
        ->and(Storage::disk('public')->allFiles())->toBe([])
        ->and(MediaFile::query()->count())->toBe(0);

    app()->instance(MediaRegistrar::class, $registrar);

    $picker->set('uploads', [UploadedFile::fake()->createWithContent('notes.txt', 'Successful retry.')])
        ->assertHasNoErrors()
        ->assertSet('tab', 'library')
        ->assertDontSee('Media registry unavailable.');

    expect(MediaFile::query()->count())->toBe(1)
        ->and(FileUploadConfiguration::storage()->allFiles(FileUploadConfiguration::directory()))->toBe([]);
});

it('preserves successful files but stays on upload when another file cannot be persisted', function (): void {
    config()->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    config()->set('filament-media.authorization.enabled', false);
    Storage::fake('public');
    MediaFile::creating(function (MediaFile $media): void {
        if ($media->original_name === 'failed.txt') {
            throw new RuntimeException('Media registry unavailable.');
        }
    });

    $picker = Livewire::test(MediaPickerBrowser::class, ['pickerId' => 'partial-picker'])
        ->set('uploads', [
            UploadedFile::fake()->createWithContent('saved.txt', 'Successful upload.'),
            UploadedFile::fake()->createWithContent('failed.txt', 'Failed upload.'),
        ])->assertHasErrors(['uploads'])
        ->assertSet('tab', 'upload')
        ->assertSet('uploads', [])
        ->assertSee('Media registry unavailable.');

    $media = MediaFile::query()->sole();

    expect($media->original_name)->toBe('saved.txt')
        ->and($picker->get('selectedId'))->toBe($media->getKey())
        ->and(Storage::disk('public')->allFiles())->toBe([$media->path])
        ->and(FileUploadConfiguration::storage()->allFiles(FileUploadConfiguration::directory()))->toBe([]);
});

it('shows Livewire upload validation errors without changing tabs or leaving temporary files', function (): void {
    config()->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    config()->set('filament-media.authorization.enabled', false);
    config()->set('livewire.temporary_file_upload.rules', ['required', 'file', 'max:1']);
    Storage::fake('public');

    $picker = Livewire::test(MediaPickerBrowser::class, ['pickerId' => 'oversized-picker'])
        ->set('tab', 'upload')
        ->set('uploads', [UploadedFile::fake()->create('oversized.txt', 2, 'text/plain')])
        ->assertHasErrors(['uploads.0'])
        ->assertSet('tab', 'upload');

    $document = new DOMDocument;
    @$document->loadHTML($picker->html());
    $alerts = (new DOMXPath($document))->query('//div[@role="alert"]');

    expect($alerts)->toHaveCount(1)
        ->and($alerts->item(0)->textContent)->toContain('1 kilobytes')
        ->and(FileUploadConfiguration::storage()->allFiles(FileUploadConfiguration::directory()))->toBe([])
        ->and(MediaFile::query()->count())->toBe(0);
});

it('hides drag and drop uploads and rejects uploads when permission is missing', function (): void {
    config()->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));

    $picker = Livewire::test(MediaPickerBrowser::class, ['pickerId' => 'unauthorized-picker']);

    $document = new DOMDocument;
    @$document->loadHTML($picker->html());
    $xpath = new DOMXPath($document);

    expect($xpath->query('//input[@type="file"] | //*[@*[name()="x-on:drop.prevent"]]'))->toHaveCount(0);

    $picker->set('uploads', [UploadedFile::fake()->createWithContent('notes.txt', 'Forbidden.')])
        ->assertForbidden();

    expect(MediaFile::query()->count())->toBe(0);
});

it('rejects automatic uploads to a disk other than the media disk', function (): void {
    config()->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    config()->set('filament-media.authorization.enabled', false);
    config()->set('filesystems.disks.other', ['driver' => 'local']);
    Storage::fake('public');
    Storage::fake('other');

    Livewire::test(MediaPickerBrowser::class, ['pickerId' => 'other-disk-picker', 'disk' => 'other'])
        ->set('uploads', [UploadedFile::fake()->createWithContent('notes.txt', 'Forbidden.')])
        ->assertForbidden();

    expect(MediaFile::query()->count())->toBe(0);
});
