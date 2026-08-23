@php
    $pickerId = $getPickerId();
    $selectedMedia = $getSelectedMediaData();
    $acceptedMimeTypes = $getAcceptedMimeTypes();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        class="filament-media-picker-field"
        x-data="{
            state: $wire.$entangle('{{ $getStatePath() }}'),
            open: false,
            pickerId: @js($pickerId),
            selected: @js($selectedMedia),
        }"
        @filament-media-selected.window="
            if ($event.detail.pickerId !== pickerId) return;
            state = $event.detail.id;
            selected = $event.detail.media;
            open = false;
        "
    >
        <div class="filament-media-picker-field__selection" x-show="selected" x-cloak>
            <div class="filament-media-picker-field__preview">
                <template x-if="selected && selected.url">
                    <img :src="selected.url" alt="">
                </template>
                <template x-if="selected && !selected.url">
                    <div class="filament-media-picker-field__file-icon">
                        <x-filament::icon icon="heroicon-o-document" />
                    </div>
                </template>
            </div>

            <div class="filament-media-picker-field__details">
                <strong x-text="selected?.name"></strong>
                <span x-text="selected ? [selected.mime_type, selected.size].filter(Boolean).join(' · ') : ''"></span>
            </div>

            @unless ($isDisabled())
                <div class="filament-media-picker-field__actions">
                    <button type="button" class="filament-media-picker-button" @click="open = true">
                        <x-filament::icon icon="heroicon-o-photo" />
                        <span>{{ __('filament-media::media-library.actions.replace') }}</span>
                    </button>
                    <button
                        type="button"
                        class="filament-media-picker-button filament-media-picker-button--danger"
                        @click="state = null; selected = null"
                    >
                        <x-filament::icon icon="heroicon-o-x-mark" />
                        <span>{{ __('filament-media::media-library.actions.clear') }}</span>
                    </button>
                </div>
            @endunless
        </div>

        <div class="filament-media-picker-field__empty" x-show="!selected" x-cloak>
            <x-filament::icon icon="heroicon-o-photo" />
            <span>{{ __('filament-media::media-library.no_selection') }}</span>
            @unless ($isDisabled())
                <button type="button" class="filament-media-picker-button filament-media-picker-button--primary" @click="open = true">
                    {{ __('filament-media::media-library.actions.select') }}
                </button>
            @endunless
        </div>

        @unless ($isDisabled())
            <div
                    class="filament-media-picker-modal"
                    x-show="open"
                    x-cloak
                    x-transition.opacity
                    @keydown.escape.window="open = false"
                    role="dialog"
                    aria-modal="true"
                    aria-label="{{ __('filament-media::media-library.picker_title') }}"
                >
                    <div class="filament-media-picker-modal__backdrop" @click="open = false"></div>
                    <div class="filament-media-picker-modal__panel" @click.stop>
                        <div class="filament-media-picker-modal__header">
                            <div>
                                <h2>{{ __('filament-media::media-library.picker_title') }}</h2>
                                <p>{{ __('filament-media::media-library.picker_description') }}</p>
                            </div>
                            <button type="button" @click="open = false" title="{{ __('filament-media::media-library.actions.close') }}">
                                <x-filament::icon icon="heroicon-o-x-mark" />
                            </button>
                        </div>

                        <div class="filament-media-picker-modal__content">
                            @livewire('media-library.media-picker-browser', [
                                'pickerId' => $pickerId,
                                'acceptedMimeTypes' => $acceptedMimeTypes,
                                'initialId' => $getState(),
                            ], key($pickerId))
                        </div>
                    </div>
                </div>
        @endunless
    </div>

    @once
        <style>
            [x-cloak] { display: none !important; }
            .filament-media-picker-field { width: 100%; }
            .filament-media-picker-field__selection { display: grid; grid-template-columns: 82px minmax(0, 1fr) auto; gap: 12px; align-items: center; padding: 10px; border: 1px solid rgb(229 231 235); border-radius: 12px; background: rgb(249 250 251); }
            .dark .filament-media-picker-field__selection { background: rgb(31 41 55); border-color: rgb(75 85 99); }
            .filament-media-picker-field__preview { width: 82px; height: 68px; border-radius: 9px; overflow: hidden; display: grid; place-items: center; background: rgb(229 231 235); }
            .dark .filament-media-picker-field__preview { background: rgb(17 24 39); }
            .filament-media-picker-field__preview img { width: 100%; height: 100%; object-fit: cover; }
            .filament-media-picker-field__file-icon svg { width: 32px; height: 32px; color: rgb(107 114 128); }
            .filament-media-picker-field__details { min-width: 0; display: grid; gap: 5px; }
            .filament-media-picker-field__details strong { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 13px; color: rgb(31 41 55); }
            .dark .filament-media-picker-field__details strong { color: rgb(243 244 246); }
            .filament-media-picker-field__details span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 11px; color: rgb(107 114 128); direction: ltr; text-align: right; }
            .filament-media-picker-field__actions { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }
            .filament-media-picker-button { display: inline-flex; align-items: center; justify-content: center; gap: 5px; min-height: 34px; padding: 6px 10px; border: 1px solid rgb(209 213 219); border-radius: 8px; font-size: 12px; font-weight: 600; color: rgb(55 65 81); background: white; }
            .dark .filament-media-picker-button { background: rgb(17 24 39); border-color: rgb(75 85 99); color: rgb(229 231 235); }
            .filament-media-picker-button svg { width: 16px; height: 16px; }
            .filament-media-picker-button--primary { background: rgb(37 99 235); border-color: rgb(37 99 235); color: white; }
            .dark .filament-media-picker-button--primary { background: rgb(37 99 235); border-color: rgb(37 99 235); color: white; }
            .filament-media-picker-button--danger { color: rgb(220 38 38); }
            .filament-media-picker-field__empty { min-height: 106px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; border: 1px dashed rgb(209 213 219); border-radius: 12px; color: rgb(107 114 128); background: rgb(249 250 251); padding: 14px; }
            .dark .filament-media-picker-field__empty { background: rgb(31 41 55); border-color: rgb(75 85 99); }
            .filament-media-picker-field__empty > svg { width: 26px; height: 26px; }
            .filament-media-picker-field__empty > span { font-size: 12px; }
            .filament-media-picker-modal { position: fixed; inset: 0; z-index: 9999; display: grid; place-items: center; padding: 22px; direction: rtl; }
            .filament-media-picker-modal__backdrop { position: absolute; inset: 0; background: rgb(0 0 0 / .72); backdrop-filter: blur(2px); }
            .filament-media-picker-modal__panel { position: relative; width: min(1180px, 96vw); height: min(760px, 92vh); display: grid; grid-template-rows: auto minmax(0, 1fr); overflow: hidden; border: 1px solid rgb(75 85 99 / .55); border-radius: 15px; background: white; box-shadow: 0 24px 70px rgb(0 0 0 / .35); }
            .dark .filament-media-picker-modal__panel { background: rgb(17 24 39); }
            .filament-media-picker-modal__header { min-height: 76px; padding: 14px 18px; display: flex; justify-content: space-between; align-items: center; gap: 16px; border-bottom: 1px solid rgb(229 231 235); }
            .dark .filament-media-picker-modal__header { border-color: rgb(55 65 81); }
            .filament-media-picker-modal__header h2 { font-size: 16px; font-weight: 700; color: rgb(31 41 55); }
            .dark .filament-media-picker-modal__header h2 { color: rgb(243 244 246); }
            .filament-media-picker-modal__header p { margin-top: 3px; font-size: 11px; color: rgb(107 114 128); }
            .filament-media-picker-modal__header button { width: 34px; height: 34px; display: grid; place-items: center; color: rgb(107 114 128); border-radius: 8px; }
            .filament-media-picker-modal__header button:hover { background: rgb(243 244 246); }
            .dark .filament-media-picker-modal__header button:hover { background: rgb(31 41 55); }
            .filament-media-picker-modal__header svg { width: 20px; height: 20px; }
            .filament-media-picker-modal__content { min-height: 0; overflow: hidden; }
            @media (max-width: 720px) {
                .filament-media-picker-field__selection { grid-template-columns: 62px minmax(0, 1fr); }
                .filament-media-picker-field__preview { width: 62px; height: 56px; }
                .filament-media-picker-field__actions { grid-column: 1 / -1; }
                .filament-media-picker-modal { padding: 7px; }
                .filament-media-picker-modal__panel { width: 100%; height: 96vh; }
            }
        </style>
    @endonce
</x-dynamic-component>
