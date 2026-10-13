<?php

namespace Sarmadict\FilamentMedia\Filament\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;
use Filament\Support\Enums\Width;
use InvalidArgumentException;
use Sarmadict\FilamentMedia\Contracts\MediaRepository;
use Sarmadict\FilamentMedia\Contracts\PreviewUrlResolver;
use Sarmadict\FilamentMedia\Models\MediaFile;
use Sarmadict\FilamentMedia\Support\Disk;
use Sarmadict\FilamentMedia\Support\FileType;

class MediaPicker extends Field
{
    protected const MODAL_WIDTHS = [
        'normal' => '1180px',
        'xs' => '20rem',
        'sm' => '24rem',
        'md' => '28rem',
        'lg' => '32rem',
        'xl' => '36rem',
        '2xl' => '42rem',
        '3xl' => '48rem',
        '4xl' => '56rem',
        '5xl' => '64rem',
        '6xl' => '72rem',
        '7xl' => '80rem',
        'screen' => '100%',
    ];

    public const RESULT_ID = 'id';

    public const RESULT_MEDIA_ID = 'media_id';

    public const RESULT_UUID = 'uuid';

    public const RESULT_FILE_NAME = 'file_name';

    public const RESULT_PATH = 'path';

    public const RESULT_FULL_PATH = 'full_path';

    protected string $view = 'filament-media::forms.components.media-picker';

    /**
     * @var list<string>|Closure
     */
    protected array|Closure $acceptedMimeTypes = [];

    protected string|Closure|null $disk = null;

    protected bool|Closure $isInline = false;

    protected bool|Closure $shouldSubmitParentFormOnSelection = false;

    protected Width|string|Closure|null $modalWidth = null;

    protected string|Closure|null $resultType = null;

    protected string|Closure|null $uploadPath = null;

    protected string|Closure|null $uploadPathType = null;

    protected string|Closure|null $uploadFilenameType = null;

    protected string|Closure|null $uploadDateFormat = null;

    public function modalWidth(Width|string|Closure|null $width = null): static
    {
        $this->modalWidth = $width;

        return $this;
    }

    public function getModalWidth(): string
    {
        $width = $this->evaluate($this->modalWidth)
            ?? config('filament-media.picker.modal_width', 'normal');

        if ($width instanceof Width) {
            $width = $width->value;
        }

        if (! is_string($width) || ! array_key_exists($width, self::MODAL_WIDTHS)) {
            throw new InvalidArgumentException(sprintf(
                'MediaPicker modal width must be one of [%s].',
                implode(', ', array_keys(self::MODAL_WIDTHS)),
            ));
        }

        return $width;
    }

    public function getModalMaxWidth(): string
    {
        return self::MODAL_WIDTHS[$this->getModalWidth()];
    }

    public function uploadPath(string|Closure|null $path): static
    {
        $this->uploadPath = $path;

        return $this;
    }

    public function getUploadPath(): ?string
    {
        return $this->evaluate($this->uploadPath);
    }

    public function uploadPathType(string|Closure|null $pathType): static
    {
        $this->uploadPathType = $pathType;

        return $this;
    }

    public function getUploadPathType(): ?string
    {
        return $this->evaluate($this->uploadPathType);
    }

    public function uploadFilenameType(string|Closure|null $filenameType): static
    {
        $this->uploadFilenameType = $filenameType;

        return $this;
    }

    public function getUploadFilenameType(): ?string
    {
        return $this->evaluate($this->uploadFilenameType);
    }

    public function uploadDateFormat(string|Closure|null $dateFormat): static
    {
        $this->uploadDateFormat = $dateFormat;

        return $this;
    }

    public function getUploadDateFormat(): ?string
    {
        return $this->evaluate($this->uploadDateFormat);
    }

    public function resultType(string|Closure $resultType): static
    {
        $this->resultType = $resultType;

        return $this;
    }

    public function getResultType(): string
    {
        $resultType = $this->evaluate($this->resultType)
            ?? config('filament-media.picker.result_type', self::RESULT_ID);
        $supported = [self::RESULT_ID, self::RESULT_MEDIA_ID, self::RESULT_UUID, self::RESULT_FILE_NAME, self::RESULT_PATH, self::RESULT_FULL_PATH];

        if (! in_array($resultType, $supported, true)) {
            throw new InvalidArgumentException(sprintf(
                'MediaPicker result type must be one of [%s].',
                implode(', ', $supported),
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
            'media_id' => $media->getKey(),
            'uuid' => $media->uuid,
            'name' => $media->original_name ?: $media->file_name,
            'file_name' => $media->file_name,
            'disk' => $media->disk,
            'path' => $media->path,
            'full_path' => $media->path,
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

        $resultType = $this->getResultType();

        if (in_array($resultType, [self::RESULT_ID, self::RESULT_MEDIA_ID], true)) {
            return is_numeric($state)
                ? $repository->findActiveById((int) $state)
                : null;
        }

        if (! is_string($state) || blank($state)) {
            return null;
        }

        if ($resultType === self::RESULT_UUID) {
            return $repository->query()->where('uuid', $state)->where('state', true)->first();
        }

        if ($resultType === self::RESULT_FILE_NAME) {
            // Legacy files may share a basename, even on the same disk.
            $matches = $repository->query()
                ->where('file_name', $state)
                ->where('state', true)
                ->whereIn('disk', $this->candidateDisks())
                ->limit(2)
                ->get();

            return $matches->count() === 1 ? $matches->first() : null;
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
