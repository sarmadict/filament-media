<?php

use Sarmadict\FilamentMedia\Filament\Forms\Components\MediaPicker;

it('uses media ids as the default result type', function (): void {
    expect(MediaPicker::make('cover_media_id')->getResultType())
        ->toBe(MediaPicker::RESULT_ID);
});

it('can return the media path', function (): void {
    expect(
        MediaPicker::make('cover_path')
            ->resultType(MediaPicker::RESULT_PATH)
            ->getResultType()
    )->toBe(MediaPicker::RESULT_PATH);
});

it('evaluates a dynamic result type', function (): void {
    expect(
        MediaPicker::make('cover')
            ->resultType(fn (): string => MediaPicker::RESULT_PATH)
            ->getResultType()
    )->toBe(MediaPicker::RESULT_PATH);
});

it('rejects unsupported result types', function (): void {
    MediaPicker::make('cover')
        ->resultType('url')
        ->getResultType();
})->throws(InvalidArgumentException::class);
