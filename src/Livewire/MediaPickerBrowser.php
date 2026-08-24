<?php

namespace Sarmadict\FilamentMedia\Livewire;

use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Sarmadict\FilamentMedia\Contracts\MediaRepository;
use Sarmadict\FilamentMedia\Contracts\PreviewUrlResolver;
use Sarmadict\FilamentMedia\Models\MediaFile;
use Sarmadict\FilamentMedia\Services\AuthorizationManager;
use Sarmadict\FilamentMedia\Services\FileUploader;
use Sarmadict\FilamentMedia\Support\Disk;
use Sarmadict\FilamentMedia\Support\FileType;
use Throwable;

class MediaPickerBrowser extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $pickerId;
    public string $disk = '';
    public ?string $lockedDisk = null;
    public string $search = '';
    public string $tab = 'library';
    public ?int $selectedId = null;

    /** @var list<string> */
    public array $acceptedMimeTypes = [];

    /** @var array<int, mixed> */
    public array $uploads = [];

    public function mount(string $pickerId, array $acceptedMimeTypes = [], int|string|null $initialId = null, ?string $disk = null): void
    {
        $this->pickerId = $pickerId;
        $this->acceptedMimeTypes = array_values($acceptedMimeTypes);
        $this->selectedId = is_numeric($initialId) ? (int) $initialId : null;

        if ($disk !== null) {
            abort_unless(in_array($disk, Disk::all(), true), 404);
            $this->lockedDisk = $disk;
        }

        $this->disk = $this->lockedDisk ?? Disk::default();
    }

    /** @return list<string> */
    public function disks(): array
    {
        return $this->lockedDisk !== null ? [$this->lockedDisk] : Disk::all();
    }

    public function canUpload(): bool
    {
        return app(AuthorizationManager::class)->allows('create') && $this->disk === Disk::upload();
    }

    public function updatedSearch(): void
    {
        $this->resetPage('mediaPickerPage');
    }

    public function updatedDisk(string $disk): void
    {
        abort_unless(in_array($disk, $this->disks(), true), 404);
        $this->selectedId = null;
        $this->resetPage('mediaPickerPage');
    }

    public function select(int $id): void
    {
        $media = $this->baseQuery()->whereKey($id)->first();

        if ($media !== null) {
            $this->selectedId = (int) $media->getKey();
        }
    }

    public function selectAndConfirm(int $id): void
    {
        $media = $this->baseQuery()->whereKey($id)->first();

        if ($media === null) {
            return;
        }

        $this->selectedId = (int) $media->getKey();
        $this->dispatchSelection($media);
    }

    public function confirmSelection(): void
    {
        if ($this->selectedId === null) {
            Notification::make()
                ->title(__('filament-media::media-library.notifications.selection_missing'))
                ->warning()
                ->send();

            return;
        }

        $media = $this->baseQuery()->whereKey($this->selectedId)->first();

        if ($media === null) {
            $this->selectedId = null;

            return;
        }

        $this->dispatchSelection($media);
    }

    public function storeUploads(): void
    {
        abort_unless(in_array($this->disk, $this->disks(), true), 404);
        abort_unless($this->disk === Disk::upload(), 403);
        app(AuthorizationManager::class)->authorize('create');

        if ($this->uploads === []) {
            return;
        }

        $lastMedia = null;

        foreach ($this->uploads as $file) {
            $mimeType = method_exists($file, 'getMimeType') ? $file->getMimeType() : null;

            if (! FileType::mimeMatches($mimeType, $this->acceptedMimeTypes)) {
                Notification::make()
                    ->title(__('filament-media::media-library.invalid_file_type', [
                        'name' => method_exists($file, 'getClientOriginalName') ? $file->getClientOriginalName() : '',
                    ]))
                    ->danger()
                    ->send();

                continue;
            }

            try {
                $lastMedia = app(FileUploader::class)->upload($file);
            } catch (Throwable $exception) {
                report($exception);

                Notification::make()
                    ->title(__('filament-media::media-library.notifications.operation_failed'))
                    ->body($exception->getMessage())
                    ->danger()
                    ->send();
            }
        }

        $this->uploads = [];
        $this->tab = 'library';
        $this->resetPage('mediaPickerPage');

        if ($lastMedia !== null) {
            $this->selectedId = (int) $lastMedia->getKey();

            Notification::make()
                ->title(__('filament-media::media-library.notifications.uploaded'))
                ->success()
                ->send();
        }
    }

    public function render()
    {
        return view('filament-media::livewire.media-picker-browser', [
            'mediaItems' => $this->mediaItems(),
            'selectedMedia' => $this->selectedMedia(),
        ]);
    }

    /** @return Builder<MediaFile> */
    private function baseQuery(): Builder
    {
        $query = app(MediaRepository::class)
            ->query()
            ->where('state', true)
            ->where('disk', $this->disk);

        if ($this->acceptedMimeTypes !== []) {
            $query->where(function (Builder $query): void {
                foreach ($this->acceptedMimeTypes as $acceptedMimeType) {
                    if (str_ends_with($acceptedMimeType, '/*')) {
                        $query->orWhere('mime_type', 'like', substr($acceptedMimeType, 0, -1).'%');
                    } else {
                        $query->orWhere('mime_type', $acceptedMimeType);
                    }
                }
            });
        }

        return $query;
    }

    private function dispatchSelection(MediaFile $media): void
    {
        $this->dispatch(
            'filament-media-selected',
            pickerId: $this->pickerId,
            id: (int) $media->getKey(),
            media: $this->mediaData($media),
        );
    }

    /** @return array<string, mixed>|null */
    private function selectedMedia(): ?array
    {
        if ($this->selectedId === null) {
            return null;
        }

        $media = $this->baseQuery()->whereKey($this->selectedId)->first();

        return $media !== null ? $this->mediaData($media) : null;
    }

    private function mediaItems(): LengthAwarePaginator
    {
        $query = $this->baseQuery();

        if (trim($this->search) !== '') {
            $search = '%'.trim($this->search).'%';

            $query->where(function (Builder $query) use ($search): void {
                $query
                    ->where('original_name', 'like', $search)
                    ->orWhere('file_name', 'like', $search)
                    ->orWhere('path', 'like', $search);
            });
        }

        $paginator = $query
            ->latest('id')
            ->paginate(24, ['*'], 'mediaPickerPage');

        $paginator->getCollection()->transform(fn (MediaFile $media): array => $this->mediaData($media));

        return $paginator;
    }

    /** @return array<string, mixed> */
    private function mediaData(MediaFile $media): array
    {
        return [
            'id' => (int) $media->getKey(),
            'name' => $media->original_name ?: $media->file_name,
            'file_name' => $media->file_name,
            'disk' => $media->disk,
            'path' => $media->path,
            'mime_type' => $media->mime_type,
            'size' => FileType::humanSize((int) $media->size_bytes),
            'extension' => $media->extension,
            'width' => $media->width,
            'height' => $media->height,
            'url' => FileType::isImageMime($media->mime_type)
                ? app(PreviewUrlResolver::class)->forMedia($media)
                : null,
        ];
    }
}
