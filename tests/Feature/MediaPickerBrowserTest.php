<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
                && $parameters['media']['path'] === $media->path,
        );
});

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
