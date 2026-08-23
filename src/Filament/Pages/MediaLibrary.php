<?php

namespace Sarmadict\FilamentMedia\Filament\Pages;

use Sarmadict\FilamentMedia\Exceptions\DirectoryNotEmptyException;
use Sarmadict\FilamentMedia\Exceptions\MediaInUseException;
use Sarmadict\FilamentMedia\Services\AuthorizationManager;
use Sarmadict\FilamentMedia\Services\FileBrowser;
use Sarmadict\FilamentMedia\Services\DirectoryRenamer;
use Sarmadict\FilamentMedia\Services\FileDeleter;
use Sarmadict\FilamentMedia\Services\FileUploader;
use Sarmadict\FilamentMedia\Services\MediaRegistrar;
use Sarmadict\FilamentMedia\Support\Disk;
use Sarmadict\FilamentMedia\Support\Path;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Throwable;
use UnitEnum;

class MediaLibrary extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-folder-open';
    protected static string|UnitEnum|null $navigationGroup = null;
    protected static ?int $navigationSort = null;

    public static function shouldRegisterNavigation(): bool
    {
        return (bool) config('filament-media.navigation.enabled', true);
    }

    protected string $view = 'filament-media::filament.pages.media-library';

    public string $disk = '';
    public string $path = '';
    public string $search = '';
    public string $type = 'all';
    public string $viewMode = 'grid';
    public int $page = 1;
    public int $perPage = 30;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return config('filament-media.navigation.group', 'Media');
    }

    public static function getNavigationSort(): ?int
    {
        return config('filament-media.navigation.sort', 5);
    }

    public static function getNavigationLabel(): string
    {
        return config('filament-media.navigation.label')
            ?: __('filament-media::media-library.browser_title');
    }

    public function getTitle(): string
    {
        return __('filament-media::media-library.browser_title');
    }

    public static function canAccess(): bool
    {
        return app(AuthorizationManager::class)->allows('view-any');
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->disk = Disk::default();
    }


    public function canCreateMedia(): bool
    {
        return $this->hasPermission('media_files.create');
    }

    public function canUpdateMedia(): bool
    {
        return $this->hasPermission('media_files.update');
    }

    public function canDeleteMedia(): bool
    {
        return $this->hasPermission('media_files.delete');
    }

    /**
     * @return list<string>
     */
    public function disks(): array
    {
        return Disk::all();
    }

    /**
     * @return array<string, string>
     */
    public function typeOptions(): array
    {
        return [
            'all' => __('filament-media::media-library.types.all'),
            'images' => __('filament-media::media-library.types.images'),
            'videos' => __('filament-media::media-library.types.videos'),
            'audio' => __('filament-media::media-library.types.audio'),
            'documents' => __('filament-media::media-library.types.documents'),
            'archives' => __('filament-media::media-library.types.archives'),
            'other' => __('filament-media::media-library.types.other'),
        ];
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, per_page: int, last_page: int}
     */
    public function entries(): array
    {
        $this->assertCurrentDisk();

        try {
            return app(FileBrowser::class)->browse(
                disk: $this->disk,
                path: $this->path,
                search: $this->search,
                type: $this->type,
                page: $this->page,
                perPage: $this->perPage,
            );
        } catch (Throwable $exception) {
            report($exception);

            return [
                'items' => [],
                'total' => 0,
                'page' => 1,
                'per_page' => $this->perPage,
                'last_page' => 1,
            ];
        }
    }

    /**
     * @return list<array{label: string, path: string}>
     */
    public function breadcrumbs(): array
    {
        return Path::breadcrumbs($this->path);
    }

    public function updatedDisk(string $disk): void
    {
        abort_unless(in_array($disk, $this->disks(), true), 404);

        $this->path = '';
        $this->page = 1;
    }

    public function updatedSearch(): void
    {
        $this->page = 1;
    }

    public function updatedType(): void
    {
        $this->page = 1;
    }

    public function updatedPerPage(): void
    {
        $this->perPage = in_array($this->perPage, [30, 60, 100], true) ? $this->perPage : 30;
        $this->page = 1;
    }

    public function openDirectory(string $encodedPath): void
    {
        $this->assertCurrentDisk();
        $path = $this->decodePath($encodedPath);

        abort_unless(in_array($path, Storage::disk($this->disk)->directories(Path::parent($path)), true), 404);

        $this->path = $path;
        $this->page = 1;
    }

    public function navigate(string $encodedPath): void
    {
        $this->assertCurrentDisk();
        $path = $this->decodePath($encodedPath);

        if ($path !== '') {
            abort_unless(Storage::disk($this->disk)->directoryExists($path), 404);
        }

        $this->path = $path;
        $this->page = 1;
    }

    public function previousPage(): void
    {
        $this->page = max(1, $this->page - 1);
    }

    public function nextPage(): void
    {
        $lastPage = $this->entries()['last_page'];
        $this->page = min($lastPage, $this->page + 1);
    }

    public function registerFile(string $encodedPath): void
    {
        $this->assertCurrentDisk();
        $this->authorizePermission('media_files.create');
        $path = $this->decodePath($encodedPath);

        try {
            app(MediaRegistrar::class)->register($this->disk, $path);

            Notification::make()
                ->title(__('filament-media::media-library.notifications.registered'))
                ->success()
                ->send();
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title(__('filament-media::media-library.notifications.operation_failed'))
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function deleteFile(string $encodedPath): void
    {
        $this->assertCurrentDisk();
        $this->authorizePermission('media_files.delete');
        $path = $this->decodePath($encodedPath);

        try {
            app(FileDeleter::class)->deleteFile($this->disk, $path);

            Notification::make()
                ->title(__('filament-media::media-library.notifications.deleted'))
                ->success()
                ->send();
        } catch (MediaInUseException $exception) {
            $details = collect($exception->usages)
                ->map(fn (array $usage): string => "{$usage['label']}: {$usage['count']}")
                ->implode('، ');

            Notification::make()
                ->title(__('filament-media::media-library.notifications.in_use'))
                ->body($details)
                ->danger()
                ->persistent()
                ->send();
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title(__('filament-media::media-library.notifications.operation_failed'))
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function renameDirectory(string $encodedPath, string $newName): void
    {
        $this->assertCurrentDisk();
        $this->authorizePermission('media_files.update');
        $path = $this->decodePath($encodedPath);

        try {
            app(DirectoryRenamer::class)->rename($this->disk, $path, $newName);

            Notification::make()
                ->title(__('filament-media::media-library.notifications.directory_renamed'))
                ->success()
                ->send();
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title(__('filament-media::media-library.notifications.operation_failed'))
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function deleteDirectory(string $encodedPath): void
    {
        $this->assertCurrentDisk();
        $this->authorizePermission('media_files.delete');
        $path = $this->decodePath($encodedPath);

        try {
            app(FileDeleter::class)->deleteDirectory($this->disk, $path);

            Notification::make()
                ->title(__('filament-media::media-library.notifications.directory_deleted'))
                ->success()
                ->send();
        } catch (DirectoryNotEmptyException) {
            Notification::make()
                ->title(__('filament-media::media-library.notifications.directory_not_empty'))
                ->warning()
                ->send();
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title(__('filament-media::media-library.notifications.operation_failed'))
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function uploadFilesAction(): Action
    {
        return Action::make('uploadFiles')
            ->label(__('filament-media::media-library.actions.upload'))
            ->icon('heroicon-o-arrow-up-tray')
            ->visible(fn (): bool => $this->hasPermission('media_files.create'))
            ->schema([
                FileUpload::make('files')
                    ->label(__('filament-media::media-library.upload.files'))
                    ->multiple()
                    ->storeFiles(false)
                    ->required(),
            ])
            ->action(function (array $data): void {
                $this->assertCurrentDisk();
                $this->authorizePermission('media_files.create');

                foreach ((array) ($data['files'] ?? []) as $file) {
                    app(FileUploader::class)->upload(
                        $file,
                        $this->disk,
                        $this->path !== '' ? $this->path : null,
                    );
                }

                Notification::make()
                    ->title(__('filament-media::media-library.notifications.uploaded'))
                    ->success()
                    ->send();
            });
    }

    public function createFolderAction(): Action
    {
        return Action::make('createFolder')
            ->label(__('filament-media::media-library.actions.create_folder'))
            ->icon('heroicon-o-folder-plus')
            ->visible(fn (): bool => $this->hasPermission('media_files.create'))
            ->schema([
                TextInput::make('name')
                    ->label(__('filament-media::media-library.folder_name'))
                    ->required()
                    ->maxLength(150)
                    ->rule('not_regex:/[\\\\\/]/'),
            ])
            ->action(function (array $data): void {
                $this->assertCurrentDisk();
                $this->authorizePermission('media_files.create');
                $name = trim((string) ($data['name'] ?? ''));

                if ($name === '' || $name === '.' || $name === '..') {
                    return;
                }

                Storage::disk($this->disk)->makeDirectory(Path::join($this->path, $name));

                Notification::make()
                    ->title(__('filament-media::media-library.notifications.directory_created'))
                    ->success()
                    ->send();
            });
    }

    private function decodePath(string $encodedPath): string
    {
        $decoded = base64_decode($encodedPath, true);
        abort_if($decoded === false, 404);

        try {
            return Path::normalize($decoded);
        } catch (InvalidArgumentException) {
            abort(404);
        }
    }

    private function assertCurrentDisk(): void
    {
        abort_unless(in_array($this->disk, $this->disks(), true), 404);
    }

    private function authorizePermission(string $permission): void
    {
        abort_unless($this->hasPermission($permission), 403);
    }

    private function hasPermission(string $permission): bool
    {
        $action = match ($permission) {
            'media_files.view-any' => 'view-any',
            'media_files.create' => 'create',
            'media_files.update' => 'update',
            'media_files.delete' => 'delete',
            default => $permission,
        };

        return app(AuthorizationManager::class)->allows($action);
    }
}
