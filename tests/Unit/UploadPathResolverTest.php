<?php

use Sarmadict\FilamentMedia\Services\UploadPathResolver;

it('appends date directories to the configured base path', function (): void {
    config()->set('filament-media.upload.date_directories.enabled', true);
    config()->set('filament-media.upload.date_directories.format', 'Y/m/d');

    $path = app(UploadPathResolver::class)->resolve('media', new \DateTimeImmutable('2026-12-10'));

    expect($path)->toBe('media/2026/12/10');
});

it('supports a date-rooted path with an empty base path', function (): void {
    config()->set('filament-media.upload.date_directories.enabled', true);
    config()->set('filament-media.upload.date_directories.format', 'Y/m/d');

    $path = app(UploadPathResolver::class)->resolve('', new \DateTimeImmutable('2026-12-10'));

    expect($path)->toBe('2026/12/10');
});

it('does not append the current date twice', function (): void {
    config()->set('filament-media.upload.date_directories.enabled', true);
    config()->set('filament-media.upload.date_directories.format', 'Y/m/d');

    $path = app(UploadPathResolver::class)->resolve('media/2026/12/10', new \DateTimeImmutable('2026-12-10'));

    expect($path)->toBe('media/2026/12/10');
});

it('can disable date directories', function (): void {
    config()->set('filament-media.upload.date_directories.enabled', false);

    $path = app(UploadPathResolver::class)->resolve('uploads', new \DateTimeImmutable('2026-12-10'));

    expect($path)->toBe('uploads');
});

it('uses a custom date format', function (): void {
    config()->set('filament-media.upload.date_directories.enabled', true);
    config()->set('filament-media.upload.date_directories.format', 'Y/m');

    $path = app(UploadPathResolver::class)->resolve('uploads', new \DateTimeImmutable('2026-12-10'));

    expect($path)->toBe('uploads/2026/12');
});
