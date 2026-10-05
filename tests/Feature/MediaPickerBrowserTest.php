<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
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
