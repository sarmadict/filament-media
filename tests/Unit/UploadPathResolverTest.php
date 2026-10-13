<?php

use Sarmadict\FilamentMedia\Services\UploadPathResolver;

it('appends date directories to the configured base path', function (): void {
    config()->set('filament-media.upload.date_format', 'Y/m/d');

    $path = app(UploadPathResolver::class)->resolve('media', new DateTimeImmutable('2026-12-10'));

    expect($path)->toBe('media/2026/12/10');
});

it('uses the media uuid prefix independently of the filename', function (): void {
    $path = app(UploadPathResolver::class)->resolve(
        basePath: 'assets',
        uuid: '019a1234-5678-7000-8000-123456789012',
        pathType: 'uuid_prefix',
    );

    expect($path)->toBe('assets/01');
});

it('lets per-upload path types and date formats override the global settings', function (): void {
    config()->set('filament-media.upload.path_type', 'uuid_prefix');

    $resolver = app(UploadPathResolver::class);
    $date = new DateTimeImmutable('2026-12-10');

    expect($resolver->resolve('assets', $date, pathType: 'date'))->toBe('assets/2026/12/10')
        ->and($resolver->resolve('assets', $date, pathType: 'flat'))->toBe('assets')
        ->and($resolver->resolve('assets', $date, pathType: 'date', dateFormat: 'Y/m'))->toBe('assets/2026/12');
});

it('requires a valid uuid for prefix paths', function (?string $uuid): void {
    app(UploadPathResolver::class)->resolve(uuid: $uuid, pathType: 'uuid_prefix');
})->with([null, '', '../outside'])->throws(InvalidArgumentException::class);

it('supports a date-rooted path with an empty base path', function (): void {
    config()->set('filament-media.upload.date_format', 'Y/m/d');

    $path = app(UploadPathResolver::class)->resolve('', new DateTimeImmutable('2026-12-10'));

    expect($path)->toBe('2026/12/10');
});

it('does not append the current date twice', function (): void {
    config()->set('filament-media.upload.date_format', 'Y/m/d');

    $path = app(UploadPathResolver::class)->resolve('media/2026/12/10', new DateTimeImmutable('2026-12-10'));

    expect($path)->toBe('media/2026/12/10');
});

it('uses the configured base path exactly for flat uploads', function (): void {
    config()->set('filament-media.upload.path_type', 'flat');
    config()->set('filament-media.upload.date_format', '');

    $path = app(UploadPathResolver::class)->resolve('uploads', new DateTimeImmutable('2026-12-10'));

    expect($path)->toBe('uploads');
});

it('uses a custom date format', function (): void {
    config()->set('filament-media.upload.date_format', 'Y/m');

    $path = app(UploadPathResolver::class)->resolve('uploads', new DateTimeImmutable('2026-12-10'));

    expect($path)->toBe('uploads/2026/12');
});
