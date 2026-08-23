<?php

namespace Sarmadict\FilamentMedia;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Sarmadict\FilamentMedia\Contracts\MediaRepository;
use Sarmadict\FilamentMedia\Contracts\MediaUsageResolver;
use Sarmadict\FilamentMedia\Contracts\PreviewUrlResolver;
use Sarmadict\FilamentMedia\Livewire\MediaPickerBrowser;
use Sarmadict\FilamentMedia\Repositories\EloquentMediaRepository;
use Sarmadict\FilamentMedia\Services\ConfigurableMediaUsageResolver;
use Sarmadict\FilamentMedia\Services\DefaultPreviewUrlResolver;

class FilamentMediaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $configPath = __DIR__.'/../config/filament-media.php';
        $overrides = (array) config('filament-media', []);

        $this->mergeConfigFrom($configPath, 'filament-media');
        config()->set('filament-media', array_replace_recursive(require $configPath, $overrides));

        $this->app->singleton(MediaRepository::class, function ($app): MediaRepository {
            $class = config('filament-media.repository', EloquentMediaRepository::class);

            return $app->make($class);
        });

        $this->app->singleton(PreviewUrlResolver::class, function ($app): PreviewUrlResolver {
            $class = config('filament-media.preview_url_resolver', DefaultPreviewUrlResolver::class);

            return $app->make($class);
        });

        $this->app->singleton(MediaUsageResolver::class, function ($app): MediaUsageResolver {
            $class = config('filament-media.usage.resolver', ConfigurableMediaUsageResolver::class);

            return $app->make($class);
        });
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'filament-media');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'filament-media');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        Livewire::component('filament-media.media-picker-browser', MediaPickerBrowser::class);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/filament-media.php' => config_path('filament-media.php'),
            ], 'filament-media-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/filament-media'),
            ], 'filament-media-views');

            $this->publishes([
                __DIR__.'/../resources/lang' => lang_path('vendor/filament-media'),
            ], 'filament-media-translations');
        }
    }
}
