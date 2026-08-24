@php
    $canUpload = $this->canUpload();
@endphp

<div class="filament-media-picker-browser" dir="{{ config('filament-media.ui.direction') ?: (in_array(app()->getLocale(), ['fa', 'ar', 'he', 'ur'], true) ? 'rtl' : 'ltr') }}">
    <div class="filament-media-picker-browser__tabs">
        <button type="button" wire:click="$set('tab', 'library')" @class(['is-active' => $tab === 'library'])>
            {{ __('filament-media::media-library.library') }}
        </button>
        @if ($canUpload)
            <button type="button" wire:click="$set('tab', 'upload')" @class(['is-active' => $tab === 'upload'])>
                {{ __('filament-media::media-library.upload_tab') }}
            </button>
        @endif
    </div>

    @if ($tab === 'upload' && $canUpload)
        <div class="filament-media-picker-upload">
            <div class="filament-media-picker-upload__dropzone">
                <x-filament::icon icon="heroicon-o-cloud-arrow-up" />
                <strong>{{ __('filament-media::media-library.actions.upload') }}</strong>
                <p>{{ __('filament-media::media-library.upload_here') }}</p>

                <label>
                    <span>{{ __('filament-media::media-library.choose_files') }}</span>
                    <input
                        type="file"
                        wire:model="uploads"
                        @if ($acceptedMimeTypes !== []) accept="{{ implode(',', $acceptedMimeTypes) }}" @endif
                        multiple
                    >
                </label>

                @if ($uploads !== [])
                    <div class="filament-media-picker-upload__selected">
                        {{ count($uploads) }} {{ __('filament-media::media-library.selected') }}
                    </div>
                @endif

                <button
                    type="button"
                    class="filament-media-picker-confirm"
                    wire:click="storeUploads"
                    wire:loading.attr="disabled"
                    wire:target="uploads,storeUploads"
                    @disabled($uploads === [])
                >
                    <x-filament::icon icon="heroicon-o-arrow-up-tray" />
                    {{ __('filament-media::media-library.actions.upload') }}
                </button>
            </div>
        </div>
    @else
        <div class="filament-media-picker-browser__toolbar">
            <select wire:model.live="disk" aria-label="{{ __('filament-media::media-library.disk') }}">
                @foreach ($this->disks() as $availableDisk)
                    <option value="{{ $availableDisk }}">{{ $availableDisk }}</option>
                @endforeach
            </select>

            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('filament-media::media-library.picker_search') }}"
            >
        </div>

        <div class="filament-media-picker-browser__workspace">
            <div class="filament-media-picker-browser__body">
            @if ($mediaItems->isEmpty())
                <div class="filament-media-picker-browser__empty">
                    <x-filament::icon icon="heroicon-o-photo" />
                    <span>{{ __('filament-media::media-library.empty') }}</span>
                </div>
            @else
                <div class="filament-media-picker-browser__grid">
                    @foreach ($mediaItems as $media)
                        @php
                            $isImage = \Sarmadict\FilamentMedia\Support\FileType::isImageMime($media['mime_type']);
                            $isVideo = \Sarmadict\FilamentMedia\Support\FileType::isVideoMime($media['mime_type']);
                            $isAudio = \Sarmadict\FilamentMedia\Support\FileType::isAudioMime($media['mime_type']);
                        @endphp

                        <button
                            type="button"
                            x-data="{ clickTimer: null }"
                            x-on:click="
                                clearTimeout(clickTimer);
                                clickTimer = setTimeout(() => $wire.select({{ $media['id'] }}), 220);
                            "
                            x-on:dblclick.stop.prevent="
                                clearTimeout(clickTimer);
                                clickTimer = null;
                                $wire.selectAndConfirm({{ $media['id'] }});
                            "
                            @class(['filament-media-picker-item', 'is-selected' => $selectedId === $media['id']])
                            wire:key="picker-media-{{ $media['id'] }}"
                        >
                            <div class="filament-media-picker-item__preview">
                                @if ($isImage && $media['url'])
                                    <img src="{{ $media['url'] }}" alt="" loading="lazy">
                                @elseif ($isVideo)
                                    <x-filament::icon icon="heroicon-o-film" />
                                @elseif ($isAudio)
                                    <x-filament::icon icon="heroicon-o-musical-note" />
                                @else
                                    <x-filament::icon icon="heroicon-o-document" />
                                @endif
                            </div>
                            <div class="filament-media-picker-item__name" title="{{ $media['name'] }}">{{ $media['name'] }}</div>
                            <div class="filament-media-picker-item__meta">{{ $media['size'] }}</div>

                            @if ($selectedId === $media['id'])
                                <span class="filament-media-picker-item__check">
                                    <x-filament::icon icon="heroicon-o-check" />
                                </span>
                            @endif
                        </button>
                    @endforeach
                </div>
            @endif
            </div>

            <aside class="filament-media-picker-browser__details">
                @if ($selectedMedia)
                    @php
                        $selectedIsImage = \Sarmadict\FilamentMedia\Support\FileType::isImageMime($selectedMedia['mime_type']);
                        $selectedIsVideo = \Sarmadict\FilamentMedia\Support\FileType::isVideoMime($selectedMedia['mime_type']);
                        $selectedIsAudio = \Sarmadict\FilamentMedia\Support\FileType::isAudioMime($selectedMedia['mime_type']);
                    @endphp

                    <div class="filament-media-picker-details__preview">
                        @if ($selectedIsImage && $selectedMedia['url'])
                            <img src="{{ $selectedMedia['url'] }}" alt="">
                        @elseif ($selectedIsVideo)
                            <x-filament::icon icon="heroicon-o-film" />
                        @elseif ($selectedIsAudio)
                            <x-filament::icon icon="heroicon-o-musical-note" />
                        @else
                            <x-filament::icon icon="heroicon-o-document" />
                        @endif
                    </div>

                    <strong class="filament-media-picker-details__name">{{ $selectedMedia['name'] }}</strong>

                    <dl class="filament-media-picker-details__list">
                        <div><dt>{{ __('filament-media::media-library.file_name') }}</dt><dd>{{ $selectedMedia['file_name'] }}</dd></div>
                        <div><dt>{{ __('filament-media::media-library.mime_type') }}</dt><dd dir="ltr">{{ $selectedMedia['mime_type'] }}</dd></div>
                        <div><dt>{{ __('filament-media::media-library.file_size') }}</dt><dd dir="ltr">{{ $selectedMedia['size'] }}</dd></div>
                        <div><dt>{{ __('filament-media::media-library.disk') }}</dt><dd dir="ltr">{{ $selectedMedia['disk'] }}</dd></div>
                        <div><dt>{{ __('filament-media::media-library.file_path') }}</dt><dd dir="ltr">{{ $selectedMedia['path'] }}</dd></div>
                        @if ($selectedMedia['width'] && $selectedMedia['height'])
                            <div><dt>{{ __('filament-media::media-library.dimensions') }}</dt><dd dir="ltr">{{ $selectedMedia['width'] }} × {{ $selectedMedia['height'] }}</dd></div>
                        @endif
                    </dl>
                @else
                    <div class="filament-media-picker-details__empty">
                        <x-filament::icon icon="heroicon-o-cursor-arrow-rays" />
                        <span>{{ __('filament-media::media-library.no_selection') }}</span>
                    </div>
                @endif
            </aside>
        </div>

        <div class="filament-media-picker-browser__footer">
            <div class="filament-media-picker-browser__pagination">
                <button
                    type="button"
                    wire:click="previousPage('mediaPickerPage')"
                    @disabled($mediaItems->onFirstPage())
                >
                    <x-filament::icon icon="heroicon-o-chevron-right" />
                </button>
                <span>{{ $mediaItems->currentPage() }} / {{ max(1, $mediaItems->lastPage()) }}</span>
                <button
                    type="button"
                    wire:click="nextPage('mediaPickerPage')"
                    @disabled(! $mediaItems->hasMorePages())
                >
                    <x-filament::icon icon="heroicon-o-chevron-left" />
                </button>
            </div>

            <button
                type="button"
                class="filament-media-picker-confirm"
                wire:click="confirmSelection"
                @disabled($selectedId === null)
            >
                <x-filament::icon icon="heroicon-o-check" />
                {{ __('filament-media::media-library.actions.confirm') }}
            </button>
        </div>
    @endif

    @once
        <style>
            .filament-media-picker-browser { height: 100%; min-height: 0; display: grid; grid-template-rows: auto auto minmax(0, 1fr) auto; color: rgb(31 41 55); }
            .dark .filament-media-picker-browser { color: rgb(229 231 235); }
            .filament-media-picker-browser__tabs { height: 48px; padding: 0 18px; display: flex; align-items: end; gap: 18px; border-bottom: 1px solid rgb(229 231 235); }
            .dark .filament-media-picker-browser__tabs { border-color: rgb(55 65 81); }
            .filament-media-picker-browser__tabs button { height: 48px; padding: 0 4px; font-size: 13px; color: rgb(107 114 128); border-bottom: 2px solid transparent; }
            .filament-media-picker-browser__tabs button.is-active { color: rgb(37 99 235); border-bottom-color: rgb(37 99 235); font-weight: 700; }
            .filament-media-picker-browser__toolbar { padding: 12px 18px; display: grid; grid-template-columns: 160px minmax(220px, 1fr); gap: 9px; border-bottom: 1px solid rgb(229 231 235); }
            .dark .filament-media-picker-browser__toolbar { border-color: rgb(55 65 81); }
            .filament-media-picker-browser__toolbar select, .filament-media-picker-browser__toolbar input { height: 38px; border: 1px solid rgb(209 213 219); border-radius: 9px; padding: 7px 10px; background: transparent; outline: none; font-size: 13px; }
            .filament-media-picker-browser__toolbar select { color-scheme: light; background-color: rgb(255 255 255); color: rgb(31 41 55); }
            .filament-media-picker-browser__toolbar select option { background-color: rgb(255 255 255); color: rgb(31 41 55); }
            .dark .filament-media-picker-browser__toolbar select, .dark .filament-media-picker-browser__toolbar input { border-color: rgb(75 85 99); color: rgb(229 231 235); }
            .dark .filament-media-picker-browser__toolbar select { color-scheme: dark; background-color: rgb(17 24 39); color: rgb(229 231 235); }
            .dark .filament-media-picker-browser__toolbar select option { background-color: rgb(17 24 39); color: rgb(229 231 235); }
            .filament-media-picker-browser__workspace { min-height: 0; display: grid; grid-template-columns: 270px minmax(0, 1fr); direction: ltr; }
            .filament-media-picker-browser__body { grid-column: 2; direction: rtl; }
            .filament-media-picker-browser__details { grid-column: 1; grid-row: 1; min-width: 0; overflow-y: auto; direction: rtl; padding: 16px; border-right: 1px solid rgb(229 231 235); background: rgb(249 250 251); }
            .dark .filament-media-picker-browser__details { border-color: rgb(55 65 81); background: rgb(17 24 39); }
            .filament-media-picker-details__preview { height: 180px; display: grid; place-items: center; overflow: hidden; border: 1px solid rgb(229 231 235); border-radius: 10px; background: white; }
            .dark .filament-media-picker-details__preview { background: rgb(31 41 55); border-color: rgb(55 65 81); }
            .filament-media-picker-details__preview img { width: 100%; height: 100%; object-fit: contain; }
            .filament-media-picker-details__preview svg { width: 54px; height: 54px; color: rgb(107 114 128); }
            .filament-media-picker-details__name { display: block; margin-top: 12px; font-size: 12px; line-height: 1.7; overflow-wrap: anywhere; }
            .filament-media-picker-details__list { display: grid; gap: 9px; margin-top: 14px; }
            .filament-media-picker-details__list div { display: grid; gap: 3px; padding-bottom: 8px; border-bottom: 1px solid rgb(229 231 235); }
            .dark .filament-media-picker-details__list div { border-color: rgb(55 65 81); }
            .filament-media-picker-details__list dt { font-size: 10px; color: rgb(107 114 128); }
            .filament-media-picker-details__list dd { font-size: 11px; overflow-wrap: anywhere; }
            .filament-media-picker-details__empty { min-height: 260px; display: grid; place-items: center; align-content: center; gap: 10px; text-align: center; color: rgb(107 114 128); font-size: 11px; }
            .filament-media-picker-details__empty svg { width: 36px; height: 36px; }
            .filament-media-picker-browser__body { min-height: 0; overflow-y: auto; padding: 16px 18px; }
            .filament-media-picker-browser__grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 10px; align-content: start; }
            .filament-media-picker-item { position: relative; min-width: 0; overflow: hidden; border: 1px solid rgb(229 231 235); border-radius: 10px; text-align: right; background: rgb(249 250 251); transition: border-color .12s, box-shadow .12s; }
            .dark .filament-media-picker-item { background: rgb(31 41 55); border-color: rgb(55 65 81); }
            .filament-media-picker-item:hover { border-color: rgb(147 197 253); }
            .filament-media-picker-item.is-selected { border-color: rgb(37 99 235); box-shadow: inset 0 0 0 1px rgb(37 99 235); }
            .filament-media-picker-item__preview { height: 100px; display: grid; place-items: center; background: rgb(243 244 246); overflow: hidden; }
            .dark .filament-media-picker-item__preview { background: rgb(17 24 39); }
            .filament-media-picker-item__preview img { width: 100%; height: 100%; object-fit: cover; }
            .filament-media-picker-item__preview svg { width: 35px; height: 35px; color: rgb(107 114 128); }
            .filament-media-picker-item__name { padding: 8px 8px 2px; font-size: 11px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .filament-media-picker-item__meta { padding: 0 8px 8px; font-size: 10px; color: rgb(107 114 128); direction: ltr; text-align: right; }
            .filament-media-picker-item__check { position: absolute; top: 6px; right: 6px; width: 24px; height: 24px; display: grid; place-items: center; border-radius: 999px; background: rgb(37 99 235); color: white; box-shadow: 0 1px 4px rgb(0 0 0 / .2); }
            .filament-media-picker-item__check svg { width: 15px; height: 15px; }
            .filament-media-picker-browser__empty { min-height: 260px; display: grid; place-items: center; align-content: center; gap: 10px; color: rgb(107 114 128); }
            .filament-media-picker-browser__empty svg { width: 40px; height: 40px; }
            .filament-media-picker-browser__footer { min-height: 62px; padding: 10px 18px; display: flex; align-items: center; justify-content: space-between; gap: 12px; border-top: 1px solid rgb(229 231 235); }
            .dark .filament-media-picker-browser__footer { border-color: rgb(55 65 81); }
            .filament-media-picker-browser__pagination { display: flex; align-items: center; gap: 7px; font-size: 11px; color: rgb(107 114 128); }
            .filament-media-picker-browser__pagination button { width: 32px; height: 32px; display: grid; place-items: center; border: 1px solid rgb(209 213 219); border-radius: 8px; }
            .dark .filament-media-picker-browser__pagination button { border-color: rgb(75 85 99); }
            .filament-media-picker-browser__pagination button:disabled, .filament-media-picker-confirm:disabled { opacity: .4; cursor: not-allowed; }
            .filament-media-picker-browser__pagination svg, .filament-media-picker-confirm svg { width: 16px; height: 16px; }
            .filament-media-picker-confirm { min-height: 36px; padding: 7px 14px; display: inline-flex; align-items: center; gap: 6px; border-radius: 8px; background: rgb(37 99 235); color: white; font-size: 12px; font-weight: 700; }
            .filament-media-picker-upload { grid-row: 2 / -1; min-height: 0; overflow-y: auto; display: grid; place-items: center; padding: 28px; }
            .filament-media-picker-upload__dropzone { width: min(580px, 100%); min-height: 330px; padding: 30px; border: 2px dashed rgb(209 213 219); border-radius: 14px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 12px; text-align: center; background: rgb(249 250 251); }
            .dark .filament-media-picker-upload__dropzone { background: rgb(31 41 55); border-color: rgb(75 85 99); }
            .filament-media-picker-upload__dropzone > svg { width: 48px; height: 48px; color: rgb(59 130 246); }
            .filament-media-picker-upload__dropzone strong { font-size: 15px; }
            .filament-media-picker-upload__dropzone p { max-width: 440px; color: rgb(107 114 128); font-size: 11px; }
            .filament-media-picker-upload__dropzone label { display: inline-flex; align-items: center; justify-content: center; min-height: 36px; padding: 7px 13px; border: 1px solid rgb(209 213 219); border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; background: white; }
            .dark .filament-media-picker-upload__dropzone label { background: rgb(17 24 39); border-color: rgb(75 85 99); }
            .filament-media-picker-upload__dropzone input { display: none; }
            .filament-media-picker-upload__selected { font-size: 11px; color: rgb(5 150 105); }
            @media (max-width: 700px) {
                .filament-media-picker-browser__toolbar { grid-template-columns: 1fr; }
                .filament-media-picker-browser__workspace { display: block; overflow-y: auto; }
                .filament-media-picker-browser__details { display: none; }
                .filament-media-picker-browser__body { overflow: visible; }
                .filament-media-picker-browser__grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
                .filament-media-picker-browser__body { padding: 10px; }
                .filament-media-picker-browser__footer { padding: 8px 10px; }
            }
        </style>
    @endonce
</div>
