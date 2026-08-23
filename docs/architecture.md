# Architecture and extension points

## Main layers

### Filament UI

- `FilamentMediaPlugin`
- `Filament\Pages\MediaLibrary`
- `Filament\Forms\Components\MediaPicker`
- `Livewire\MediaPickerBrowser`

### Filesystem services

- `FileBrowser`
- `FileUploader`
- `FileDeleter`
- `DirectoryRenamer`
- `MediaMetadataReader`
- `UploadPathResolver`

### Registry services

- `MediaRegistrar`
- `MediaRepository`
- `EloquentMediaRepository`

### Policies / integration services

- `AuthorizationManager`
- `MediaUsageResolver`
- `ConfigurableMediaUsageResolver`
- `PreviewUrlResolver`
- `DefaultPreviewUrlResolver`

### Models

- `MediaFile`
- `MediaAttachment`
- `HasMediaAttachments`

## Container bindings

The discovered service provider binds:

```text
MediaRepository      -> configured repository
PreviewUrlResolver   -> configured preview resolver
MediaUsageResolver   -> configured usage resolver
```

All are singletons resolved by Laravel's container.

## Repository override

A custom repository must implement `MediaRepository` and return `MediaFile` (or a subclass) instances.

## Preview override

A custom preview resolver can produce CDN URLs, signed controller routes, or provider-specific temporary links.

## Usage override

A custom usage resolver can query external tables, domain services, or other databases before deletion.

## Why the filesystem and registry are separate

Directory browsing should reflect actual storage contents, including legacy or manually copied files. Application relationships need stable database IDs and metadata. Keeping both concepts allows the browser to show unregistered physical files and explicitly register them without pretending that the registry is a filesystem index.
