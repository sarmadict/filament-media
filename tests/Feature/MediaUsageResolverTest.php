<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Sarmadict\FilamentMedia\Contracts\MediaUsageResolver;
use Sarmadict\FilamentMedia\Exceptions\MediaInUseException;
use Sarmadict\FilamentMedia\Services\FileDeleter;
use Sarmadict\FilamentMedia\Services\MediaRegistrar;

uses(RefreshDatabase::class);

class MediaUsageRecordForTesting extends Model
{
    protected $table = 'media_references';

    public $timestamps = false;
}

it('protects media referenced by every picker result type', function (string $attribute): void {
    Schema::create('media_references', function (Blueprint $table): void {
        $table->id();
        $table->string('media_value', 700);
        $table->string('media_disk');
    });
    Storage::fake('public');
    Storage::disk('public')->put('uploads/example.txt', 'Example.');
    $media = app(MediaRegistrar::class)->register('public', 'uploads/example.txt');
    $value = match ($attribute) {
        'id', 'media_id' => $media->getKey(),
        'full_path' => $media->path,
        default => $media->getAttribute($attribute),
    };

    $reference = new MediaUsageRecordForTesting;
    $reference->forceFill(['media_value' => $value, 'media_disk' => 'public'])->save();
    config()->set('filament-media.usage.direct_references', [[
        'model' => MediaUsageRecordForTesting::class,
        'column' => 'media_value',
        'media_attribute' => $attribute,
        'disk_column' => 'media_disk',
        'label' => 'Posts',
    ]]);

    expect(app(MediaUsageResolver::class)->usages($media))->toBe([['label' => 'Posts', 'count' => 1]])
        ->and(fn () => app(FileDeleter::class)->deleteFile('public', $media->path))->toThrow(MediaInUseException::class);

    Storage::disk('public')->assertExists($media->path);

    $reference->forceFill(['media_disk' => 'another-disk'])->save();
    expect(app(MediaUsageResolver::class)->usages($media))->toBe([]);
})->with(['id', 'media_id', 'uuid', 'file_name', 'path', 'full_path']);
