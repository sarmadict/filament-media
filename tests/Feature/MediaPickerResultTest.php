<?php

use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Component;
use Livewire\Livewire;
use Sarmadict\FilamentMedia\Filament\Forms\Components\MediaPicker;
use Sarmadict\FilamentMedia\Models\MediaFile;

uses(RefreshDatabase::class);

class MediaPickerFormForTesting extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    public array $data = [];

    public string $directory = 'posts/covers';

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            MediaPicker::make('cover')
                ->uploadPath(fn (): string => $this->directory)
                ->uploadPathType('date')
                ->uploadFilenameType('hash')
                ->uploadDateFormat('Y/m'),
            MediaPicker::make('avatar')->inline()->uploadPath('avatars')->uploadPathType('flat'),
        ]);
    }

    public function render(): string
    {
        return '<form>{{ $this->form }}</form>';
    }
}

function mediaResultField(string $resultType, mixed $value): MediaPicker
{
    $livewire = new class extends Component implements HasSchemas
    {
        use InteractsWithSchemas;

        public mixed $cover = null;
    };
    $field = MediaPicker::make('cover')->resultType($resultType)->disk('public');
    $field->container(Schema::make($livewire));
    $field->state($value);

    return $field;
}

function mediaResultRecord(string $path = 'uploads/example.txt', bool $active = true): MediaFile
{
    $media = new MediaFile;
    $media->forceFill([
        'disk' => 'public',
        'path' => $path,
        'original_name' => 'original.txt',
        'file_name' => basename($path),
        'mime_type' => 'text/plain',
        'state' => $active,
    ])->save();

    return $media;
}

it('loads an existing selection for every supported result type', function (string $resultType): void {
    $media = mediaResultRecord();
    $value = match ($resultType) {
        'id', 'media_id' => $media->getKey(),
        'full_path' => $media->path,
        default => $media->getAttribute($resultType),
    };

    $field = mediaResultField($resultType, $value);

    expect($field->getSelectedMediaData()['id'])->toBe($media->getKey())
        ->and($field->getSelectedMediaData()[$resultType])->toBe($value);
})->with(['id', 'media_id', 'uuid', 'file_name', 'path', 'full_path']);

it('does not resolve inactive or soft deleted selections', function (string $resultType): void {
    $media = mediaResultRecord(active: false);
    $value = match ($resultType) {
        'id' => $media->getKey(),
        default => $media->getAttribute($resultType),
    };
    $field = mediaResultField($resultType, $value);
    expect($field->getSelectedMediaData())->toBeNull();

    $media->forceFill(['state' => true])->save();
    $media->delete();
    expect($field->getSelectedMediaData())->toBeNull();
})->with(['id', 'uuid', 'file_name', 'path']);

it('does not resolve an ambiguous legacy filename', function (): void {
    mediaResultRecord('first/example.txt');
    mediaResultRecord('second/example.txt');

    expect(mediaResultField('file_name', 'example.txt')->getSelectedMediaData())->toBeNull();
});

it('passes upload settings from modal and inline fields to their browsers', function (): void {
    config()->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));

    $form = Livewire::test(MediaPickerFormForTesting::class);
    $document = new DOMDocument;
    @$document->loadHTML($form->html());
    $elements = (new DOMXPath($document))->query('//*[@*[name()="wire:snapshot"]]');
    $settings = [];

    foreach ($elements as $element) {
        $snapshot = json_decode($element->getAttribute('wire:snapshot'), true);

        if (($snapshot['memo']['name'] ?? null) === 'filament-media.media-picker-browser') {
            $settings[] = $snapshot['data'];
        }
    }

    expect($settings)->toHaveCount(2)
        ->and($settings[0]['uploadPath'])->toBe('posts/covers')
        ->and($settings[0]['uploadPathType'])->toBe('date')
        ->and($settings[0]['uploadFilenameType'])->toBe('hash')
        ->and($settings[0]['uploadDateFormat'])->toBe('Y/m')
        ->and($settings[1]['uploadPath'])->toBe('avatars')
        ->and($settings[1]['uploadPathType'])->toBe('flat');

    @$document->loadHTML($form->set('directory', 'posts/documents')->html());
    $updatedPath = null;

    foreach ((new DOMXPath($document))->query('//*[@*[name()="wire:snapshot"]]') as $element) {
        $snapshot = json_decode($element->getAttribute('wire:snapshot'), true);

        if (($snapshot['memo']['name'] ?? null) === 'filament-media.media-picker-browser') {
            $updatedPath = $snapshot['data']['uploadPath'];
        }
    }

    expect($updatedPath)->toBe('posts/documents');
});
