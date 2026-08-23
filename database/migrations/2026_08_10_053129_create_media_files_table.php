<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $mediaFiles = (string) config('filament-media.tables.media_files', 'media_files');
        $mediaAttachments = (string) config('filament-media.tables.media_attachments', 'media_attachments');
        $users = (string) config('filament-media.tables.users', 'users');

        if (! Schema::hasTable($mediaFiles)) {
            Schema::create($mediaFiles, function (Blueprint $table) use ($users) {
                $table->id();
                $table->string('disk', 64)->default('public');
                $table->string('path', 700);
                $table->string('original_name');
                $table->string('file_name');
                $table->string('mime_type', 191);
                $table->string('extension', 32)->nullable();
                $table->unsignedBigInteger('size_bytes')->default(0);
                $table->unsignedInteger('width')->nullable();
                $table->unsignedInteger('height')->nullable();
                $table->unsignedInteger('duration_seconds')->nullable();
                $table->string('alt_text')->nullable();
                $table->string('caption')->nullable();
                $table->json('metadata')->nullable();
                $table->boolean('state')->default(true)->index();
                $table->foreignId('created_by')->nullable()->constrained($users)->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained($users)->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['disk', 'file_name']);
            });
        }

        if (! Schema::hasTable($mediaAttachments)) {
            Schema::create($mediaAttachments, function (Blueprint $table) use ($mediaFiles, $users) {
                $table->id();
                $table->foreignId('media_file_id')->constrained($mediaFiles)->cascadeOnDelete();
                $table->morphs('attachable');
                $table->string('collection', 64)->default('default');
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('state')->default(true)->index();
                $table->foreignId('created_by')->nullable()->constrained($users)->nullOnDelete();
                $table->timestamps();

                $table->unique(
                    ['media_file_id', 'attachable_type', 'attachable_id', 'collection'],
                    'media_attachments_unique'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists((string) config('filament-media.tables.media_attachments', 'media_attachments'));
        Schema::dropIfExists((string) config('filament-media.tables.media_files', 'media_files'));
    }
};
