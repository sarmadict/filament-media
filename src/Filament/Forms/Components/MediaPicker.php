<?php

namespace Sarmadict\FilamentMedia\Filament\Forms\Components;

use Sarmadict\FilamentMedia\Contracts\MediaRepository;
use Sarmadict\FilamentMedia\Contracts\PreviewUrlResolver;
use Sarmadict\FilamentMedia\Support\FileType;
use Closure;
use Filament\Forms\Components\Field;

class MediaPicker extends Field
{
    protected string $view = 'filament-media::forms.components.media-picker';

    /**
     * @var list<string>|Closure
     */
    protected array|Closure $acceptedMimeTypes = [];

    public function acceptedMimeTypes(array|Closure $acceptedMimeTypes): static
    {
        $this->acceptedMimeTypes = $acceptedMimeTypes;

        return $this;
    }

    public function images(): static
    {
        return $this->acceptedMimeTypes(['image/*']);
    }

    public function videos(): static
    {
        return $this->acceptedMimeTypes(['video/*']);
    }

    public function audio(): static
    {
        return $this->acceptedMimeTypes(['audio/*']);
    }

    public function documents(): static
    {
        return $this->acceptedMimeTypes([
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
            'text/csv',
        ]);
    }

    /**
     * @return list<string>
     */
    public function getAcceptedMimeTypes(): array
    {
        return array_values((array) $this->evaluate($this->acceptedMimeTypes));
    }

    public function getPickerId(): string
    {
        return 'media-picker-' . md5($this->getStatePath());
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getSelectedMediaData(): ?array
    {
        $id = $this->getState();

        if (! is_numeric($id)) {
            return null;
        }

        $media = app(MediaRepository::class)->findActiveById((int) $id);

        if ($media === null) {
            return null;
        }

        return [
            'id' => $media->getKey(),
            'name' => $media->original_name ?: $media->file_name,
            'file_name' => $media->file_name,
            'mime_type' => $media->mime_type,
            'size' => FileType::humanSize((int) $media->size_bytes),
            'url' => FileType::isImageMime($media->mime_type)
                ? app(PreviewUrlResolver::class)->forMedia($media)
                : null,
        ];
    }
}
