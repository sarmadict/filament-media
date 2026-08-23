# Installation

## From Packagist or another Composer repository

```bash
composer require sarmadict/filament-media
```

Laravel discovers `Sarmadict\FilamentMedia\FilamentMediaServiceProvider` automatically through Composer package discovery. Do not add the service provider manually unless package discovery is disabled in the host application.

Publish the configuration when you need to customize defaults:

```bash
php artisan vendor:publish --tag=filament-media-config
```

Run migrations:

```bash
php artisan migrate
```

The package loads its migrations automatically. The config, views, and translations are optional publishable resources.

## Register the Filament panel plugin

Add the plugin to every Filament panel that should expose the file browser:

```php
use Filament\Panel;
use Sarmadict\FilamentMedia\FilamentMediaPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(FilamentMediaPlugin::make());
}
```

Laravel package discovery loads the service provider; Filament plugin registration is still a panel-level decision.

## Optional publish commands

```bash
php artisan vendor:publish --tag=filament-media-config
php artisan vendor:publish --tag=filament-media-views
php artisan vendor:publish --tag=filament-media-translations
```

Do not publish package files unless you actually need host-level overrides. Keeping the package resources in `vendor/` reduces upgrade friction.

## Local package development

A monorepo application can use a Composer path repository:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "packages/sarmadict/filament-media",
            "options": {
                "versions": {
                    "sarmadict/filament-media": "1.0.0"
                }
            }
        }
    ],
    "require": {
        "sarmadict/filament-media": "^1.0"
    }
}
```

This is how the bundled project consumes the extracted package before it is published remotely.
