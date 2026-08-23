<?php

namespace Sarmadict\FilamentMedia\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Sarmadict\FilamentMedia\Models\MediaAttachment;
use Sarmadict\FilamentMedia\Models\MediaFile;

trait HasMediaAttachments
{
    public function mediaAttachments(): MorphMany
    {
        /** @var class-string<MediaAttachment> $attachmentModel */
        $attachmentModel = config('filament-media.models.media_attachment', MediaAttachment::class);

        return $this->morphMany($attachmentModel, 'attachable');
    }

    public function mediaFiles(): MorphToMany
    {
        /** @var class-string<MediaFile> $mediaModel */
        $mediaModel = config('filament-media.models.media_file', MediaFile::class);
        $table = (string) config('filament-media.tables.media_attachments', 'media_attachments');

        return $this->morphToMany($mediaModel, 'attachable', $table)
            ->withPivot(['id', 'collection', 'sort_order', 'state', 'created_by'])
            ->withTimestamps();
    }
}
