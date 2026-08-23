<?php

use Sarmadict\FilamentMedia\Contracts\MediaRepository;
use Sarmadict\FilamentMedia\Contracts\MediaUsageResolver;
use Sarmadict\FilamentMedia\Contracts\PreviewUrlResolver;
use Sarmadict\FilamentMedia\Repositories\EloquentMediaRepository;
use Sarmadict\FilamentMedia\Services\ConfigurableMediaUsageResolver;
use Sarmadict\FilamentMedia\Services\DefaultPreviewUrlResolver;

it('registers package contracts', function (): void {
    expect(app(MediaRepository::class))->toBeInstanceOf(EloquentMediaRepository::class)
        ->and(app(PreviewUrlResolver::class))->toBeInstanceOf(DefaultPreviewUrlResolver::class)
        ->and(app(MediaUsageResolver::class))->toBeInstanceOf(ConfigurableMediaUsageResolver::class);
});

it('loads package views and translations', function (): void {
    expect(view()->exists('filament-media::livewire.media-picker-browser'))->toBeTrue()
        ->and(trans('filament-media::media-library.title'))->not->toBe('filament-media::media-library.title');
});
