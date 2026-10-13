<?php

use Filament\Support\Enums\Width;
use Sarmadict\FilamentMedia\Filament\Forms\Components\MediaPicker;

it('preserves the current modal size by default and when the config key is missing', function (): void {
    expect(MediaPicker::make('cover')->getModalWidth())->toBe('normal')
        ->and(MediaPicker::make('cover')->getModalMaxWidth())->toBe('1180px');

    config()->set('filament-media.picker', []);

    expect(MediaPicker::make('cover')->getModalWidth())->toBe('normal');
});

it('inherits the global modal width and allows field overrides', function (Width|string $width): void {
    config()->set('filament-media.picker.modal_width', $width);

    expect(MediaPicker::make('cover')->getModalWidth())->toBe('screen')
        ->and(MediaPicker::make('cover')->modalWidth('normal')->getModalWidth())->toBe('normal')
        ->and(MediaPicker::make('cover')->modalWidth(Width::FiveExtraLarge)->getModalWidth())->toBe('5xl');
})->with(['screen', Width::Screen]);

it('supports the Filament modal width scale as enums and strings', function (Width $width, string $maxWidth): void {
    expect(MediaPicker::make('cover')->modalWidth($width)->getModalWidth())->toBe($width->value)
        ->and(MediaPicker::make('cover')->modalWidth($width)->getModalMaxWidth())->toBe($maxWidth)
        ->and(MediaPicker::make('cover')->modalWidth($width->value)->getModalMaxWidth())->toBe($maxWidth);
})->with([
    [Width::ExtraSmall, '20rem'],
    [Width::FiveExtraLarge, '64rem'],
    [Width::Screen, '100%'],
]);

it('evaluates modal width closures and inherits the config for null overrides', function (): void {
    config()->set('filament-media.picker.modal_width', 'screen');

    expect(MediaPicker::make('cover')->modalWidth(fn (): Width => Width::SevenExtraLarge)->getModalWidth())->toBe('7xl')
        ->and(MediaPicker::make('cover')->modalWidth(fn (): string => 'normal')->getModalWidth())->toBe('normal')
        ->and(MediaPicker::make('cover')->modalWidth(fn () => null)->getModalWidth())->toBe('screen')
        ->and(MediaPicker::make('cover')->modalWidth('normal')->modalWidth()->getModalWidth())->toBe('screen');
});

it('rejects unsupported modal widths in fields and config', function (bool $global): void {
    $field = MediaPicker::make('cover');

    if ($global) {
        config()->set('filament-media.picker.modal_width', 'invalid');
    } else {
        $field->modalWidth('invalid');
    }

    $field->getModalWidth();
})->with([true, false])->throws(InvalidArgumentException::class, 'MediaPicker modal width must be one of');

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
