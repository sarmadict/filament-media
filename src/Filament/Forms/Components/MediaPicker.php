<?php

namespace Sarmadict\FilamentMedia\Filament\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;
use InvalidArgumentException;
use Sarmadict\FilamentMedia\Contracts\MediaRepository;
use Sarmadict\FilamentMedia\Contracts\PreviewUrlResolver;
use Sarmadict\FilamentMedia\Models\MediaFile;
use Sarmadict\FilamentMedia\Support\Disk;
use Sarmadict\FilamentMedia\Support\FileType;

class MediaPicker extends Field
{
    public const RESULT_ID = 'id';

    public const RESULT_PATH = 'path';

    protected string $view = 'filament-media::forms.components.media-picker';

    /**
     * @var list<string>|Closure
     */
    protected array|Closure $acceptedMimeTypes = [];

    protected string|Closure|null $disk = null;

    protected bool|Closure $isInline = false;

    protected bool|Closure $shouldSubmitParentFormOnSelection = false;

    protected string|Closure $resultType = self::RESULT_ID;

    public function resultType(string|Closure $resultType): static
    {
        $this->resultType = $resultType;

        return $this;
    }

    public function getResultType(): string
    {
        $resultType = $this->evaluate($this->resultType);

        if (! in_array($resultType, [self::RESULT_ID, self::RESULT_PATH], true)) {
            throw new InvalidArgumentException(sprintf(
                'MediaPicker result type must be [%s] or [%s].',
                self::RESULT_ID,
                self::RESULT_PATH,
            ));
        }

        return $resultType;
    }

    public function disk(string|Closure|null $disk): static
    {
        $this->disk = $disk;

        return $this;
    }

    public function getDisk(): ?string
    {
        $disk = $this->evaluate($this->disk);

        return filled($disk) ? (string) $disk : null;
    }

    public function inline(bool|Closure $condition = true): static
    {
        $this->isInline = $condition;

        return $this;
    }

    public function isInline(): bool
    {
        return (bool) $this->evaluate($this->isInline);
    }

    public function submitParentFormOnSelection(bool|Closure $condition = true): static
    {
        $this->shouldSubmitParentFormOnSelection = $condition;

        return $this;
    }

    public function shouldSubmitParentFormOnSelection(): bool
    {
        return (bool) $this->evaluate($this->shouldSubmitParentFormOnSelection);
    }

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
        return 'media-picker-'.md5($this->getStatePath());
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getSelectedMediaData(): ?array
    {
        $media = $this->resolveSelectedMedia();

        if ($media === null) {
            return null;
        }

        return [
            'id' => $media->getKey(),
            'name' => $media->original_name ?: $media->file_name,
            'file_name' => $media->file_name,
            'disk' => $media->disk,
            'path' => $media->path,
            'mime_type' => $media->mime_type,
            'size' => FileType::humanSize((int) $media->size_bytes),
            'url' => FileType::isImageMime($media->mime_type)
                ? app(PreviewUrlResolver::class)->forMedia($media)
                : null,
        ];
    }

    private function resolveSelectedMedia(): ?MediaFile
    {
        $state = $this->getState();
        $repository = app(MediaRepository::class);

        if ($this->getResultType() === self::RESULT_ID) {
            return is_numeric($state)
                ? $repository->findActiveById((int) $state)
                : null;
        }

        if (! is_string($state) || blank($state)) {
            return null;
        }

        foreach ($this->candidateDisks() as $disk) {
            $media = $repository->findByLocation($disk, $state);

            if ($media !== null && $media->state) {
                return $media;
            }
        }

        return null;
    }

    /** @return list<string> */
    private function candidateDisks(): array
    {
        if (($disk = $this->getDisk()) !== null) {
            return [$disk];
        }

        return array_values(array_unique([
            Disk::default(),
            ...Disk::all(),
        ]));
    }
}
