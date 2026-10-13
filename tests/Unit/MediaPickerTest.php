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

it('inherits the global result type and allows a field override', function (): void {
    config()->set('filament-media.picker.result_type', MediaPicker::RESULT_UUID);

    expect(MediaPicker::make('cover')->getResultType())->toBe(MediaPicker::RESULT_UUID)
        ->and(MediaPicker::make('cover')->resultType(MediaPicker::RESULT_ID)->getResultType())->toBe(MediaPicker::RESULT_ID);
});

it('evaluates upload overrides in the field context', function (): void {
    $field = MediaPicker::make('cover')
        ->uploadPath(fn (): string => 'posts/covers')
        ->uploadPathType(fn (): string => 'date')
        ->uploadFilenameType(fn (): string => 'hash')
        ->uploadDateFormat(fn (): string => 'Y/m');

    expect($field->getUploadPath())->toBe('posts/covers')
        ->and($field->getUploadPathType())->toBe('date')
        ->and($field->getUploadFilenameType())->toBe('hash')
        ->and($field->getUploadDateFormat())->toBe('Y/m')
        ->and(MediaPicker::make('root')->uploadPath('')->getUploadPath())->toBe('');
});
