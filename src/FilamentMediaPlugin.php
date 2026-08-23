<?php

namespace Sarmadict\FilamentMedia;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Sarmadict\FilamentMedia\Filament\Pages\MediaLibrary;

class FilamentMediaPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'sarmadict-filament-media';
    }

    public function register(Panel $panel): void
    {
        if ((bool) config('filament-media.navigation.enabled', true)) {
            $panel->pages([
                MediaLibrary::class,
            ]);
        }
    }

    public function boot(Panel $panel): void
    {
        // Runtime services and resources are registered by the package service provider.
    }
}
