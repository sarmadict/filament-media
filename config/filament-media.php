<?php

use Sarmadict\FilamentMedia\Models\MediaAttachment;
use Sarmadict\FilamentMedia\Models\MediaFile;
use Sarmadict\FilamentMedia\Repositories\EloquentMediaRepository;
use Sarmadict\FilamentMedia\Services\ConfigurableMediaUsageResolver;
use Sarmadict\FilamentMedia\Services\DefaultPreviewUrlResolver;

return [
    'models' => [
        'media_file' => MediaFile::class,
        'media_attachment' => MediaAttachment::class,
        'user' => env('FILAMENT_MEDIA_USER_MODEL', config('auth.providers.users.model')),
    ],

    'tables' => [
        'media_files' => 'media_files',
        'media_attachments' => 'media_attachments',
        'users' => 'users',
    ],

    'repository' => EloquentMediaRepository::class,
    'preview_url_resolver' => DefaultPreviewUrlResolver::class,

    'upload' => [
        'disk' => env('FILAMENT_MEDIA_DISK', 'public'),
        'path' => env('FILAMENT_MEDIA_UPLOAD_PATH', 'uploads'),
        'visibility' => env('FILAMENT_MEDIA_VISIBILITY'),
        'date_directories' => [
            'enabled' => env('FILAMENT_MEDIA_DATE_DIRECTORIES', true),
            'format' => env('FILAMENT_MEDIA_DATE_FORMAT', 'Y/m/d'),
        ],
    ],

    'disks' => [
        // Empty means every filesystem disk configured by the host application.
        'allowed' => [],
    ],


    'ui' => [
        // null: infer RTL for common RTL locales and LTR otherwise.
        'direction' => env('FILAMENT_MEDIA_DIRECTION'),
    ],

    'navigation' => [
        'enabled' => true,
        'group' => 'Media',
        'label' => null,
        'sort' => 5,
    ],

    'authorization' => [
        'enabled' => true,
        'permissions' => [
            'view-any' => 'media_files.view-any',
            'create' => 'media_files.create',
            'update' => 'media_files.update',
            'delete' => 'media_files.delete',
        ],
    ],

    'usage' => [
        'resolver' => ConfigurableMediaUsageResolver::class,
        'check_attachments' => true,
        // Each item: ['model' => Model::class, 'column' => 'media_id', 'label' => 'Translation key or label'].
        'direct_references' => [],
    ],
];
