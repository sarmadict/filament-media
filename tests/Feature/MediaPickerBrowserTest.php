<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Sarmadict\FilamentMedia\Livewire\MediaPickerBrowser;
use Sarmadict\FilamentMedia\Models\MediaFile;

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
        ->call('storeUploads')->assertHasNoErrors();

    Livewire::test(MediaPickerBrowser::class, ['pickerId' => 'default-picker'])
        ->set('uploads', [UploadedFile::fake()->createWithContent('default.txt', 'Default.')])
        ->call('storeUploads')->assertHasNoErrors();

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

it('keeps upload readiness separate from the save loading state', function (): void {
    config()->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    config()->set('filament-media.authorization.enabled', false);

    Storage::fake('public');

    $picker = Livewire::test(MediaPickerBrowser::class, [
        'pickerId' => 'upload-picker',
        'disk' => 'public',
    ])->set('tab', 'upload');

    $document = new DOMDocument;
    @$document->loadHTML($picker->html());
    $button = (new DOMXPath($document))->query('//button[@*[name()="wire:click"]="storeUploads"]')->item(0);

    expect($button)->toBeInstanceOf(DOMElement::class)
        ->and($button->hasAttribute('disabled'))->toBeTrue()
        ->and(explode(',', $button->getAttribute('wire:target')))->not->toContain('uploads');

    $picker->set('uploads', [UploadedFile::fake()->createWithContent('notes.txt', 'Example upload.')])
        ->assertHasNoErrors()
        ->assertSee('1 '.__('filament-media::media-library.selected'));

    @$document->loadHTML($picker->html());
    $button = (new DOMXPath($document))->query('//button[@*[name()="wire:click"]="storeUploads"]')->item(0);

    expect($button->hasAttribute('disabled'))->toBeFalse();

    $picker->set('uploads', []);

    @$document->loadHTML($picker->html());
    $button = (new DOMXPath($document))->query('//button[@*[name()="wire:click"]="storeUploads"]')->item(0);

    expect($button->hasAttribute('disabled'))->toBeTrue();
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
        ->call('storeUploads')
        ->assertHasNoErrors()
        ->assertSet('uploads', [])
        ->assertSet('tab', 'library');

    $mediaFiles = MediaFile::query()->orderBy('id')->get();

    expect($mediaFiles)->toHaveCount(2)
        ->and($mediaFiles->pluck('original_name')->all())->toBe(['first.txt', 'second.txt'])
        ->and($picker->get('selectedId'))->toBe($mediaFiles->last()->getKey());

    Storage::disk('public')->assertExists($mediaFiles->pluck('path')->all());

    $picker->set('tab', 'upload')->set('uploads', [
        UploadedFile::fake()->createWithContent('third.txt', 'Another upload.'),
    ])->call('storeUploads')->assertHasNoErrors()->assertSet('uploads', []);

    expect(MediaFile::query()->count())->toBe(3);
});
