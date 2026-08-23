<?php

namespace Sarmadict\FilamentMedia\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MediaFile extends Model
{
    use SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (self $media): void {
            $userId = auth()->id();

            if ($media->created_by === null) {
                $media->created_by = $userId;
            }

            if ($media->updated_by === null) {
                $media->updated_by = $userId;
            }
        });

        static::updating(function (self $media): void {
            if (auth()->check()) {
                $media->updated_by = auth()->id();
            }
        });
    }

    public function getTable(): string
    {
        return (string) config('filament-media.tables.media_files', parent::getTable());
    }

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'state' => 'boolean',
        ];
    }

    public function attachments(): HasMany
    {
        /** @var class-string<MediaAttachment> $attachmentModel */
        $attachmentModel = config('filament-media.models.media_attachment', MediaAttachment::class);

        return $this->hasMany($attachmentModel, 'media_file_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo($this->userModel(), 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo($this->userModel(), 'updated_by');
    }

    /** @return class-string<Model> */
    private function userModel(): string
    {
        /** @var class-string<Model> $model */
        $model = config('filament-media.models.user', config('auth.providers.users.model'));

        return $model;
    }
}
