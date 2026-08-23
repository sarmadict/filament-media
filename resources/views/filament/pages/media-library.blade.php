<x-filament-panels::page>
    @php
        $result = $this->entries();
        $items = $result['items'];
        $breadcrumbs = $this->breadcrumbs();
    @endphp

    <div
        class="filament-media-browser"
        dir="{{ config('filament-media.ui.direction') ?: (in_array(app()->getLocale(), ['fa', 'ar', 'he', 'ur'], true) ? 'rtl' : 'ltr') }}"
        x-data="{
            selectedDirectory: null,
            contextMenu: {
                open: false,
                x: 0,
                y: 0,
                path: null,
                name: null,
                encodedPath: null,
            },
            renameModal: {
                open: false,
                name: '',
                encodedPath: null,
            },
            fileModal: {
                open: false,
                file: null,
            },
            locale: @js(app()->getLocale() === 'fa' ? 'fa-IR' : 'en-US'),
            deleteDirectoryConfirmation: @js(__('filament-media::media-library.confirmations.delete_directory')),
            selectDirectory(path) {
                this.selectedDirectory = path;
                this.contextMenu.open = false;
            },
            showDirectoryMenu(event, path, name, encodedPath) {
                this.selectedDirectory = path;
                const menuWidth = 190;
                const menuHeight = 104;
                this.contextMenu = {
                    open: true,
                    x: Math.max(8, Math.min(event.clientX, window.innerWidth - menuWidth - 8)),
                    y: Math.max(8, Math.min(event.clientY, window.innerHeight - menuHeight - 8)),
                    path,
                    name,
                    encodedPath,
                };
            },
            beginRename() {
                this.renameModal = {
                    open: true,
                    name: this.contextMenu.name ?? '',
                    encodedPath: this.contextMenu.encodedPath,
                };
                this.contextMenu.open = false;
                this.$nextTick(() => this.$refs.renameInput?.focus());
            },
            openFile(file) {
                this.contextMenu.open = false;
                this.fileModal = { open: true, file };
            },
            closeFile() {
                this.fileModal = { open: false, file: null };
            },
            isImage(file) {
                return file?.category === 'images' || (file?.mime_type ?? '').startsWith('image/');
            },
            isVideo(file) {
                return file?.category === 'videos' || (file?.mime_type ?? '').startsWith('video/');
            },
            isAudio(file) {
                return file?.category === 'audio' || (file?.mime_type ?? '').startsWith('audio/');
            },
            formatBytes(bytes) {
                if (bytes === null || bytes === undefined) return '—';
                if (bytes < 1024) return `${bytes} B`;
                const units = ['KB', 'MB', 'GB', 'TB'];
                let value = bytes / 1024;
                for (const unit of units) {
                    if (value < 1024 || unit === 'TB') {
                        return `${value >= 10 ? value.toFixed(1) : value.toFixed(2)} ${unit}`;
                    }
                    value /= 1024;
                }
                return `${bytes} B`;
            },
            formatDate(timestamp) {
                if (! timestamp) return '—';
                try {
                    return new Intl.DateTimeFormat(this.locale, {
                        dateStyle: 'medium',
                        timeStyle: 'short',
                    }).format(new Date(timestamp * 1000));
                } catch (error) {
                    return new Date(timestamp * 1000).toLocaleString();
                }
            },
            formatDuration(seconds) {
                if (seconds === null || seconds === undefined) return '—';
                const value = Math.max(0, Number(seconds) || 0);
                const hours = Math.floor(value / 3600);
                const minutes = Math.floor((value % 3600) / 60);
                const secs = Math.floor(value % 60);
                return hours > 0
                    ? `${hours}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`
                    : `${minutes}:${String(secs).padStart(2, '0')}`;
            },
        }"
        x-on:click.window="contextMenu.open = false"
        x-on:keydown.escape.window="contextMenu.open = false; renameModal.open = false; closeFile()"
    >
        <div class="filament-media-toolbar">
            <div class="filament-media-toolbar__filters">
                <label class="filament-media-control">
                    <span>{{ __('filament-media::media-library.disk') }}</span>
                    <select wire:model.live="disk">
                        @foreach ($this->disks() as $availableDisk)
                            <option value="{{ $availableDisk }}">{{ $availableDisk }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="filament-media-control">
                    <span>{{ __('filament-media::media-library.type') }}</span>
                    <select wire:model.live="type">
                        @foreach ($this->typeOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="filament-media-control filament-media-control--search">
                    <span class="sr-only">{{ __('filament-media::media-library.search') }}</span>
                    <input
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('filament-media::media-library.search') }}"
                    >
                </label>

                <label class="filament-media-control filament-media-control--small">
                    <span class="sr-only">{{ __('filament-media::media-library.per_page') }}</span>
                    <select wire:model.live.number="perPage">
                        @foreach ([30, 60, 100] as $size)
                            <option value="{{ $size }}">{{ $size }}</option>
                        @endforeach
                    </select>
                </label>

                <div class="filament-media-view-toggle" aria-label="{{ __('filament-media::media-library.view_grid') }}">
                    <button type="button" wire:click="$set('viewMode', 'grid')" @class(['is-active' => $viewMode === 'grid']) title="{{ __('filament-media::media-library.view_grid') }}">
                        <x-filament::icon icon="heroicon-o-squares-2x2" />
                    </button>
                    <button type="button" wire:click="$set('viewMode', 'list')" @class(['is-active' => $viewMode === 'list']) title="{{ __('filament-media::media-library.view_list') }}">
                        <x-filament::icon icon="heroicon-o-bars-3" />
                    </button>
                </div>
            </div>

            <div class="filament-media-toolbar__actions">
                {{ $this->createFolderAction }}
                {{ $this->uploadFilesAction }}
            </div>
        </div>

        <div class="filament-media-breadcrumbs">
            @foreach ($breadcrumbs as $breadcrumb)
                <button
                    type="button"
                    wire:click="navigate('{{ base64_encode($breadcrumb['path']) }}')"
                >
                    @if ($loop->first)
                        <x-filament::icon icon="heroicon-o-home" />
                    @else
                        {{ $breadcrumb['label'] }}
                    @endif
                </button>
                @unless ($loop->last)
                    <span>/</span>
                @endunless
            @endforeach
        </div>

        @if ($items === [])
            <div class="filament-media-empty">
                <x-filament::icon icon="heroicon-o-folder-open" />
                <p>{{ __('filament-media::media-library.empty') }}</p>
            </div>
        @else
            <div @class(['filament-media-grid', 'filament-media-grid--list' => $viewMode === 'list'])>
                @foreach ($items as $entry)
                    @php
                        $encodedPath = base64_encode($entry['path']);
                        $isImage = $entry['category'] === 'images' || \Sarmadict\FilamentMedia\Support\FileType::isImageMime($entry['mime_type']);
                        $isVideo = $entry['category'] === 'videos' || \Sarmadict\FilamentMedia\Support\FileType::isVideoMime($entry['mime_type']);
                        $isAudio = $entry['category'] === 'audio' || \Sarmadict\FilamentMedia\Support\FileType::isAudioMime($entry['mime_type']);
                        $size = \Sarmadict\FilamentMedia\Support\FileType::humanSize($entry['size_bytes']);
                    @endphp

                    @if ($entry['directory'])
                        <article
                            class="filament-media-card filament-media-card--directory"
                            wire:key="media-entry-{{ md5($entry['path']) }}"
                            x-bind:class="{ 'is-selected': selectedDirectory === @js($entry['path']) }"
                            x-on:click.stop="selectDirectory(@js($entry['path']))"
                            x-on:dblclick.stop="$wire.openDirectory(@js($encodedPath))"
                            x-on:contextmenu.prevent.stop="showDirectoryMenu($event, @js($entry['path']), @js($entry['name']), @js($encodedPath))"
                            tabindex="0"
                            x-on:keydown.enter.prevent="$wire.openDirectory(@js($encodedPath))"
                        >
                            <div class="filament-media-card__preview">
                                <x-filament::icon icon="heroicon-o-folder" class="filament-media-card__file-icon filament-media-card__folder-icon" />
                            </div>

                            <div class="filament-media-card__body">
                                <div class="filament-media-card__name" title="{{ $entry['name'] }}">
                                    {{ $entry['name'] }}
                                </div>
                                <div class="filament-media-card__meta">
                                    <span>{{ __('filament-media::media-library.folder') }}</span>
                                </div>
                            </div>

                            <div class="filament-media-card__actions">
                                @if ($this->canUpdateMedia())
                                    <button
                                        type="button"
                                        class="filament-media-icon-action"
                                        x-on:click.stop="showDirectoryMenu($event, @js($entry['path']), @js($entry['name']), @js($encodedPath))"
                                        title="{{ __('filament-media::media-library.actions.rename') }}"
                                    >
                                        <x-filament::icon icon="heroicon-o-ellipsis-vertical" />
                                    </button>
                                @endif

                                @if ($this->canDeleteMedia())
                                    <button
                                        type="button"
                                        class="filament-media-icon-action filament-media-icon-action--danger"
                                        wire:click.stop="deleteDirectory('{{ $encodedPath }}')"
                                        wire:confirm="{{ __('filament-media::media-library.confirmations.delete_directory') }}"
                                        title="{{ __('filament-media::media-library.actions.delete') }}"
                                    >
                                        <x-filament::icon icon="heroicon-o-trash" />
                                    </button>
                                @endif
                            </div>
                        </article>
                    @else
                        <article
                            class="filament-media-card filament-media-card--file"
                            wire:key="media-entry-{{ md5($entry['path']) }}"
                            x-on:click.stop="openFile(@js($entry))"
                            tabindex="0"
                            x-on:keydown.enter.prevent="openFile(@js($entry))"
                        >
                            <div class="filament-media-card__preview" role="button">
                                @if ($isImage && $entry['preview_url'])
                                    <img src="{{ $entry['preview_url'] }}" alt="" loading="lazy">
                                @elseif ($isVideo)
                                    <x-filament::icon icon="heroicon-o-film" class="filament-media-card__file-icon" />
                                @elseif ($isAudio)
                                    <x-filament::icon icon="heroicon-o-musical-note" class="filament-media-card__file-icon" />
                                @else
                                    <x-filament::icon icon="heroicon-o-document" class="filament-media-card__file-icon" />
                                @endif
                            </div>

                            <div class="filament-media-card__body">
                                <div class="filament-media-card__name" title="{{ $entry['name'] }}">
                                    {{ $entry['name'] }}
                                </div>

                                <div class="filament-media-card__meta">
                                    <span>{{ $size }}</span>
                                    @if ($entry['registered'])
                                        <span class="filament-media-badge filament-media-badge--registered">
                                            {{ __('filament-media::media-library.registered') }}
                                        </span>
                                    @else
                                        <span class="filament-media-badge">
                                            {{ __('filament-media::media-library.unregistered') }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="filament-media-card__actions">
                                @if (! $entry['registered'] && $this->canCreateMedia())
                                    <button
                                        type="button"
                                        class="filament-media-icon-action"
                                        wire:click.stop="registerFile('{{ $encodedPath }}')"
                                        title="{{ __('filament-media::media-library.actions.register') }}"
                                    >
                                        <x-filament::icon icon="heroicon-o-plus-circle" />
                                    </button>
                                @endif

                                @if ($this->canDeleteMedia())
                                    <button
                                        type="button"
                                        class="filament-media-icon-action filament-media-icon-action--danger"
                                        wire:click.stop="deleteFile('{{ $encodedPath }}')"
                                        wire:confirm="{{ __('filament-media::media-library.confirmations.delete_file') }}"
                                        title="{{ __('filament-media::media-library.actions.delete') }}"
                                    >
                                        <x-filament::icon icon="heroicon-o-trash" />
                                    </button>
                                @endif
                            </div>
                        </article>
                    @endif
                @endforeach
            </div>
        @endif

        <div class="filament-media-footer">
            <span>{{ number_format($result['total']) }}</span>

            <div class="filament-media-pagination">
                <button type="button" wire:click="previousPage" @disabled($result['page'] <= 1)>
                    <x-filament::icon icon="heroicon-o-chevron-right" />
                </button>
                <span>{{ $result['page'] }} / {{ $result['last_page'] }}</span>
                <button type="button" wire:click="nextPage" @disabled($result['page'] >= $result['last_page'])>
                    <x-filament::icon icon="heroicon-o-chevron-left" />
                </button>
            </div>
        </div>

        <div
            x-cloak
            x-show="contextMenu.open"
            x-transition.opacity.duration.100ms
            class="filament-media-context-menu"
            x-bind:style="`left: ${contextMenu.x}px; top: ${contextMenu.y}px`"
            x-on:click.stop
        >
            @if ($this->canUpdateMedia())
                <button type="button" x-on:click.stop="beginRename()">
                    <x-filament::icon icon="heroicon-o-pencil-square" />
                    <span>{{ __('filament-media::media-library.actions.rename') }}</span>
                </button>
            @endif

            @if ($this->canDeleteMedia())
                <button
                    type="button"
                    class="is-danger"
                    x-on:click.stop="if (window.confirm(deleteDirectoryConfirmation)) { $wire.deleteDirectory(contextMenu.encodedPath); selectedDirectory = null; } contextMenu.open = false"
                >
                    <x-filament::icon icon="heroicon-o-trash" />
                    <span>{{ __('filament-media::media-library.actions.delete') }}</span>
                </button>
            @endif
        </div>

        <div
            x-cloak
            x-show="renameModal.open"
            x-transition.opacity
            class="filament-media-modal-backdrop"
            x-on:click.self="renameModal.open = false"
        >
            <form
                class="filament-media-rename-modal"
                x-on:submit.prevent="
                    const name = renameModal.name.trim();
                    if (name !== '') {
                        $wire.renameDirectory(renameModal.encodedPath, name);
                        renameModal.open = false;
                        selectedDirectory = null;
                    }
                "
            >
                <div class="filament-media-modal-header">
                    <div>
                        <h3>{{ __('filament-media::media-library.rename_directory') }}</h3>
                        <p x-text="contextMenu.path"></p>
                    </div>
                    <button type="button" x-on:click="renameModal.open = false" aria-label="{{ __('filament-media::media-library.actions.close') }}">
                        <x-filament::icon icon="heroicon-o-x-mark" />
                    </button>
                </div>

                <div class="filament-media-rename-modal__body">
                    <label>
                        <span>{{ __('filament-media::media-library.new_directory_name') }}</span>
                        <input x-ref="renameInput" type="text" x-model="renameModal.name" maxlength="150" required>
                    </label>
                </div>

                <div class="filament-media-modal-footer">
                    <button type="button" class="filament-media-button filament-media-button--secondary" x-on:click="renameModal.open = false">
                        {{ __('filament-media::media-library.actions.cancel') }}
                    </button>
                    <button type="submit" class="filament-media-button filament-media-button--primary">
                        {{ __('filament-media::media-library.actions.rename') }}
                    </button>
                </div>
            </form>
        </div>

        <div
            x-cloak
            x-show="fileModal.open"
            x-transition.opacity
            class="filament-media-modal-backdrop filament-media-modal-backdrop--file"
            x-on:click.self="closeFile()"
        >
            <section class="filament-media-file-modal" role="dialog" aria-modal="true" aria-label="{{ __('filament-media::media-library.file_details') }}">
                <div class="filament-media-modal-header">
                    <div>
                        <h3>{{ __('filament-media::media-library.file_details') }}</h3>
                        <p x-text="fileModal.file?.name ?? ''"></p>
                    </div>
                    <button type="button" x-on:click="closeFile()" aria-label="{{ __('filament-media::media-library.actions.close') }}">
                        <x-filament::icon icon="heroicon-o-x-mark" />
                    </button>
                </div>

                <div class="filament-media-file-modal__content">
                    <div class="filament-media-file-preview">
                        <template x-if="fileModal.file && isImage(fileModal.file) && fileModal.file.preview_url">
                            <img x-bind:src="fileModal.file.preview_url" x-bind:alt="fileModal.file.name">
                        </template>

                        <template x-if="fileModal.file && isVideo(fileModal.file) && fileModal.file.preview_url">
                            <video controls preload="metadata" x-bind:src="fileModal.file.preview_url"></video>
                        </template>

                        <template x-if="fileModal.file && isAudio(fileModal.file) && fileModal.file.preview_url">
                            <div class="filament-media-file-preview__audio">
                                <x-filament::icon icon="heroicon-o-musical-note" />
                                <audio controls preload="metadata" x-bind:src="fileModal.file.preview_url"></audio>
                            </div>
                        </template>

                        <template x-if="fileModal.file && (! fileModal.file.preview_url || (! isImage(fileModal.file) && ! isVideo(fileModal.file) && ! isAudio(fileModal.file)))">
                            <div class="filament-media-file-preview__placeholder">
                                <x-filament::icon icon="heroicon-o-document" />
                                <span>{{ __('filament-media::media-library.preview_unavailable') }}</span>
                            </div>
                        </template>
                    </div>

                    <dl class="filament-media-file-details">
                        <div>
                            <dt>{{ __('filament-media::media-library.file_name') }}</dt>
                            <dd x-text="fileModal.file?.name ?? '—'"></dd>
                        </div>
                        <div x-show="fileModal.file?.original_name">
                            <dt>{{ __('filament-media::media-library.original_name') }}</dt>
                            <dd x-text="fileModal.file?.original_name ?? '—'"></dd>
                        </div>
                        <div>
                            <dt>{{ __('filament-media::media-library.file_path') }}</dt>
                            <dd class="is-path" x-text="fileModal.file?.path ?? '—'"></dd>
                        </div>
                        <div>
                            <dt>{{ __('filament-media::media-library.disk') }}</dt>
                            <dd x-text="fileModal.file?.disk ?? '—'"></dd>
                        </div>
                        <div>
                            <dt>{{ __('filament-media::media-library.mime_type') }}</dt>
                            <dd x-text="fileModal.file?.mime_type ?? '—'"></dd>
                        </div>
                        <div>
                            <dt>{{ __('filament-media::media-library.extension') }}</dt>
                            <dd x-text="fileModal.file?.extension ?? '—'"></dd>
                        </div>
                        <div>
                            <dt>{{ __('filament-media::media-library.file_size') }}</dt>
                            <dd x-text="formatBytes(fileModal.file?.size_bytes)"></dd>
                        </div>
                        <div>
                            <dt>{{ __('filament-media::media-library.last_modified') }}</dt>
                            <dd x-text="formatDate(fileModal.file?.last_modified)"></dd>
                        </div>
                        <div x-show="fileModal.file?.width || fileModal.file?.height">
                            <dt>{{ __('filament-media::media-library.dimensions') }}</dt>
                            <dd x-text="`${fileModal.file?.width ?? '—'} × ${fileModal.file?.height ?? '—'}`"></dd>
                        </div>
                        <div x-show="fileModal.file?.duration_seconds !== null && fileModal.file?.duration_seconds !== undefined">
                            <dt>{{ __('filament-media::media-library.duration') }}</dt>
                            <dd x-text="formatDuration(fileModal.file?.duration_seconds)"></dd>
                        </div>
                        <div>
                            <dt>{{ __('filament-media::media-library.registration_status') }}</dt>
                            <dd x-text="fileModal.file?.registered ? @js(__('filament-media::media-library.registered')) : @js(__('filament-media::media-library.unregistered'))"></dd>
                        </div>
                        <div>
                            <dt>{{ __('filament-media::media-library.media_id') }}</dt>
                            <dd x-text="fileModal.file?.media_id ?? '—'"></dd>
                        </div>
                    </dl>
                </div>

                <div class="filament-media-modal-footer">
                    <button type="button" class="filament-media-button filament-media-button--secondary" x-on:click="closeFile()">
                        {{ __('filament-media::media-library.actions.close') }}
                    </button>
                </div>
            </section>
        </div>
    </div>

    @once
        <style>
            [x-cloak] { display: none !important; }
            .filament-media-browser { border: 1px solid rgb(229 231 235); border-radius: 14px; overflow: hidden; background: white; }
            .dark .filament-media-browser { background: rgb(17 24 39); border-color: rgb(55 65 81); }
            .filament-media-toolbar { display: flex; align-items: end; justify-content: space-between; gap: 12px; padding: 16px; border-bottom: 1px solid rgb(229 231 235); flex-wrap: wrap; }
            .dark .filament-media-toolbar { border-color: rgb(55 65 81); }
            .filament-media-toolbar__filters, .filament-media-toolbar__actions { display: flex; align-items: end; gap: 8px; flex-wrap: wrap; }
            .filament-media-control { display: grid; gap: 5px; font-size: 12px; color: rgb(75 85 99); }
            .dark .filament-media-control { color: rgb(156 163 175); }
            .filament-media-control select, .filament-media-control input { min-height: 38px; border: 1px solid rgb(209 213 219); border-radius: 9px; background: white; color: rgb(17 24 39); padding: 7px 10px; outline: none; font-size: 14px; }
            .filament-media-control select { color-scheme: light; }
            .filament-media-control select option { background-color: white !important; color: rgb(17 24 39) !important; }
            .dark .filament-media-control select, .dark .filament-media-control input { border-color: rgb(75 85 99); background: rgb(17 24 39); color: rgb(243 244 246); }
            .dark .filament-media-control select { color-scheme: dark; }
            .dark .filament-media-control select option { background-color: rgb(17 24 39) !important; color: rgb(243 244 246) !important; }
            .filament-media-control--search input { width: min(340px, 58vw); }
            .filament-media-control--small select { min-width: 72px; }
            .filament-media-view-toggle { display: inline-flex; border: 1px solid rgb(209 213 219); border-radius: 9px; overflow: hidden; height: 38px; }
            .dark .filament-media-view-toggle { border-color: rgb(75 85 99); }
            .filament-media-view-toggle button { width: 38px; display: grid; place-items: center; color: rgb(107 114 128); }
            .filament-media-view-toggle button svg { width: 18px; height: 18px; }
            .filament-media-view-toggle button.is-active { background: rgb(239 246 255); color: rgb(37 99 235); }
            .dark .filament-media-view-toggle button.is-active { background: rgb(30 58 138 / .25); color: rgb(96 165 250); }
            .filament-media-breadcrumbs { min-height: 42px; display: flex; align-items: center; gap: 6px; padding: 8px 16px; border-bottom: 1px solid rgb(229 231 235); color: rgb(107 114 128); font-size: 13px; overflow-x: auto; }
            .dark .filament-media-breadcrumbs { border-color: rgb(55 65 81); color: rgb(156 163 175); }
            .filament-media-breadcrumbs button { display: inline-flex; align-items: center; gap: 4px; white-space: nowrap; }
            .filament-media-breadcrumbs button:hover { color: rgb(37 99 235); }
            .filament-media-breadcrumbs svg { width: 16px; height: 16px; }
            .filament-media-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 14px; padding: 18px; min-height: 360px; align-content: start; }
            .filament-media-card { position: relative; min-width: 0; border: 1px solid rgb(229 231 235); border-radius: 11px; overflow: hidden; background: rgb(249 250 251); transition: border-color .15s, box-shadow .15s, background-color .15s; outline: none; user-select: none; }
            .dark .filament-media-card { background: rgb(31 41 55); border-color: rgb(55 65 81); }
            .filament-media-card:hover { border-color: rgb(147 197 253); box-shadow: 0 4px 18px rgb(15 23 42 / .08); }
            .filament-media-card:focus-visible { box-shadow: 0 0 0 3px rgb(59 130 246 / .25); border-color: rgb(59 130 246); }
            .filament-media-card.is-selected { border-color: rgb(59 130 246); box-shadow: 0 0 0 2px rgb(59 130 246 / .22); background: rgb(239 246 255); }
            .dark .filament-media-card.is-selected { border-color: rgb(96 165 250); background: rgb(30 58 138 / .18); }
            .filament-media-card--directory, .filament-media-card--file { cursor: pointer; }
            .filament-media-card__preview { height: 128px; display: grid; place-items: center; background: rgb(243 244 246); overflow: hidden; }
            .dark .filament-media-card__preview { background: rgb(17 24 39); }
            .filament-media-card__preview img { width: 100%; height: 100%; object-fit: cover; }
            .filament-media-card__file-icon { width: 46px; height: 46px; color: rgb(107 114 128); }
            .filament-media-card__folder-icon { color: rgb(59 130 246); }
            .filament-media-card__body { padding: 10px; min-width: 0; }
            .filament-media-card__name { font-size: 13px; font-weight: 600; color: rgb(31 41 55); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .dark .filament-media-card__name { color: rgb(229 231 235); }
            .filament-media-card__meta { display: flex; align-items: center; gap: 6px; margin-top: 6px; min-height: 20px; font-size: 11px; color: rgb(107 114 128); flex-wrap: wrap; }
            .filament-media-badge { display: inline-flex; border-radius: 999px; padding: 2px 7px; background: rgb(243 244 246); color: rgb(107 114 128); }
            .dark .filament-media-badge { background: rgb(55 65 81); color: rgb(209 213 219); }
            .filament-media-badge--registered { background: rgb(236 253 245); color: rgb(5 150 105); }
            .dark .filament-media-badge--registered { background: rgb(6 78 59 / .35); color: rgb(52 211 153); }
            .filament-media-card__actions { position: absolute; top: 7px; left: 7px; display: flex; gap: 4px; opacity: 0; transition: opacity .15s; }
            .filament-media-card:hover .filament-media-card__actions, .filament-media-card:focus-within .filament-media-card__actions { opacity: 1; }
            @media (hover: none) { .filament-media-card__actions { opacity: 1; } }
            .filament-media-icon-action { width: 30px; height: 30px; display: grid; place-items: center; border-radius: 8px; background: rgb(255 255 255 / .92); color: rgb(37 99 235); box-shadow: 0 1px 4px rgb(0 0 0 / .12); }
            .dark .filament-media-icon-action { background: rgb(31 41 55 / .95); }
            .filament-media-icon-action--danger { color: rgb(220 38 38); }
            .filament-media-icon-action svg { width: 17px; height: 17px; }
            .filament-media-grid--list { display: grid; grid-template-columns: 1fr; gap: 4px; }
            .filament-media-grid--list .filament-media-card { display: grid; grid-template-columns: 64px minmax(0, 1fr) auto; align-items: center; min-height: 64px; }
            .filament-media-grid--list .filament-media-card__preview { height: 62px; }
            .filament-media-grid--list .filament-media-card__file-icon { width: 28px; height: 28px; }
            .filament-media-grid--list .filament-media-card__actions { position: static; opacity: 1; padding-inline-end: 10px; }
            .filament-media-empty { min-height: 360px; display: grid; place-items: center; align-content: center; gap: 12px; color: rgb(107 114 128); padding: 30px; }
            .filament-media-empty svg { width: 52px; height: 52px; }
            .filament-media-footer { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 12px 16px; border-top: 1px solid rgb(229 231 235); color: rgb(107 114 128); font-size: 12px; }
            .dark .filament-media-footer { border-color: rgb(55 65 81); }
            .filament-media-pagination { display: flex; align-items: center; gap: 8px; }
            .filament-media-pagination button { width: 34px; height: 34px; display: grid; place-items: center; border: 1px solid rgb(209 213 219); border-radius: 8px; }
            .dark .filament-media-pagination button { border-color: rgb(75 85 99); }
            .filament-media-pagination button:disabled { opacity: .35; cursor: not-allowed; }
            .filament-media-pagination svg { width: 16px; height: 16px; }

            .filament-media-context-menu { position: fixed; z-index: 80; min-width: 180px; padding: 6px; border: 1px solid rgb(229 231 235); border-radius: 10px; background: white; box-shadow: 0 12px 35px rgb(15 23 42 / .18); }
            .dark .filament-media-context-menu { background: rgb(31 41 55); border-color: rgb(75 85 99); }
            .filament-media-context-menu button { width: 100%; display: flex; align-items: center; gap: 9px; padding: 8px 10px; border-radius: 7px; color: rgb(31 41 55); text-align: start; font-size: 13px; }
            .dark .filament-media-context-menu button { color: rgb(229 231 235); }
            .filament-media-context-menu button:hover { background: rgb(243 244 246); }
            .dark .filament-media-context-menu button:hover { background: rgb(55 65 81); }
            .filament-media-context-menu button.is-danger { color: rgb(220 38 38); }
            .filament-media-context-menu svg { width: 17px; height: 17px; }

            .filament-media-modal-backdrop { position: fixed; inset: 0; z-index: 90; display: grid; place-items: center; padding: 20px; background: rgb(0 0 0 / .58); backdrop-filter: blur(2px); }
            .filament-media-rename-modal, .filament-media-file-modal { width: min(100%, 520px); border: 1px solid rgb(229 231 235); border-radius: 14px; overflow: hidden; background: white; box-shadow: 0 25px 70px rgb(0 0 0 / .28); }
            .filament-media-file-modal { width: min(100%, 900px); max-height: min(88vh, 820px); display: flex; flex-direction: column; }
            .dark .filament-media-rename-modal, .dark .filament-media-file-modal { background: rgb(17 24 39); border-color: rgb(55 65 81); }
            .filament-media-modal-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 15px 18px; border-bottom: 1px solid rgb(229 231 235); }
            .dark .filament-media-modal-header { border-color: rgb(55 65 81); }
            .filament-media-modal-header h3 { color: rgb(17 24 39); font-size: 16px; font-weight: 700; }
            .dark .filament-media-modal-header h3 { color: rgb(243 244 246); }
            .filament-media-modal-header p { max-width: 600px; margin-top: 3px; color: rgb(107 114 128); font-size: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .filament-media-modal-header > button { flex: none; width: 34px; height: 34px; display: grid; place-items: center; border-radius: 8px; color: rgb(107 114 128); }
            .filament-media-modal-header > button:hover { background: rgb(243 244 246); }
            .dark .filament-media-modal-header > button:hover { background: rgb(55 65 81); }
            .filament-media-modal-header > button svg { width: 19px; height: 19px; }
            .filament-media-rename-modal__body { padding: 18px; }
            .filament-media-rename-modal__body label { display: grid; gap: 7px; color: rgb(75 85 99); font-size: 13px; }
            .dark .filament-media-rename-modal__body label { color: rgb(209 213 219); }
            .filament-media-rename-modal__body input { width: 100%; min-height: 40px; border: 1px solid rgb(209 213 219); border-radius: 9px; padding: 8px 11px; background: white; color: rgb(17 24 39); outline: none; }
            .dark .filament-media-rename-modal__body input { border-color: rgb(75 85 99); background: rgb(31 41 55); color: rgb(243 244 246); }
            .filament-media-rename-modal__body input:focus { border-color: rgb(59 130 246); box-shadow: 0 0 0 3px rgb(59 130 246 / .15); }
            .filament-media-modal-footer { display: flex; align-items: center; justify-content: flex-end; gap: 8px; padding: 12px 18px; border-top: 1px solid rgb(229 231 235); }
            .dark .filament-media-modal-footer { border-color: rgb(55 65 81); }
            .filament-media-button { min-height: 36px; padding: 7px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; }
            .filament-media-button--primary { background: rgb(37 99 235); color: white; }
            .filament-media-button--primary:hover { background: rgb(29 78 216); }
            .filament-media-button--secondary { border: 1px solid rgb(209 213 219); color: rgb(55 65 81); background: white; }
            .dark .filament-media-button--secondary { border-color: rgb(75 85 99); color: rgb(229 231 235); background: rgb(31 41 55); }

            .filament-media-file-modal__content { display: grid; grid-template-columns: minmax(0, 1.45fr) minmax(260px, .75fr); min-height: 0; overflow: auto; }
            .filament-media-file-preview { min-height: 410px; display: grid; place-items: center; padding: 18px; background: rgb(243 244 246); overflow: hidden; }
            .dark .filament-media-file-preview { background: rgb(3 7 18); }
            .filament-media-file-preview img, .filament-media-file-preview video { display: block; max-width: 100%; max-height: 58vh; border-radius: 10px; object-fit: contain; background: black; }
            .filament-media-file-preview video { width: 100%; }
            .filament-media-file-preview__audio { width: min(100%, 480px); display: grid; justify-items: center; gap: 22px; }
            .filament-media-file-preview__audio > svg { width: 72px; height: 72px; color: rgb(96 165 250); }
            .filament-media-file-preview__audio audio { width: 100%; }
            .filament-media-file-preview__placeholder { display: grid; justify-items: center; gap: 12px; color: rgb(107 114 128); text-align: center; }
            .filament-media-file-preview__placeholder svg { width: 72px; height: 72px; }
            .filament-media-file-details { padding: 18px; overflow: auto; }
            .filament-media-file-details > div { display: grid; grid-template-columns: 105px minmax(0, 1fr); gap: 10px; padding: 9px 0; border-bottom: 1px solid rgb(229 231 235); }
            .dark .filament-media-file-details > div { border-color: rgb(55 65 81); }
            .filament-media-file-details dt { color: rgb(107 114 128); font-size: 12px; }
            .filament-media-file-details dd { min-width: 0; color: rgb(31 41 55); font-size: 12px; overflow-wrap: anywhere; }
            .dark .filament-media-file-details dd { color: rgb(229 231 235); }
            .filament-media-file-details dd.is-path { direction: ltr; text-align: left; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }

            @media (max-width: 700px) {
                .filament-media-toolbar { align-items: stretch; }
                .filament-media-toolbar__filters, .filament-media-toolbar__actions { width: 100%; }
                .filament-media-control--search { flex: 1 1 100%; }
                .filament-media-control--search input { width: 100%; }
                .filament-media-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); padding: 12px; gap: 9px; }
                .filament-media-modal-backdrop { padding: 10px; }
                .filament-media-file-modal__content { grid-template-columns: 1fr; }
                .filament-media-file-preview { min-height: 270px; }
                .filament-media-file-details { max-height: 38vh; }
            }
        </style>
    @endonce
</x-filament-panels::page>
