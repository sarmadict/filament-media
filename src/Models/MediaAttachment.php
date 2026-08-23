<?php

namespace Sarmadict\FilamentMedia\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MediaAttachment extends Model
{
    protected static function booted(): void
    {
        static::creating(function (self $attachment): void {
            if ($attachment->created_by === null) {
                $attachment->created_by = auth()->id();
            }
        });
    }

    public function getTable(): string
    {
        return (string) config('filament-media.tables.media_attachments', parent::getTable());
    }

    protected function casts(): array
    {
        return [
            'state' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function mediaFile(): BelongsTo
    {
        /** @var class-string<MediaFile> $mediaModel */
        $mediaModel = config('filament-media.models.media_file', MediaFile::class);

        return $this->belongsTo($mediaModel, 'media_file_id');
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        /** @var class-string<Model> $userModel */
        $userModel = config('filament-media.models.user', config('auth.providers.users.model'));

        return $this->belongsTo($userModel, 'created_by');
    }
}
