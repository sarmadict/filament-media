# Authorization

The package uses Laravel Gate abilities. It does not require a specific ACL package.

Default ability names:

```text
media_files.view-any
media_files.create
media_files.update
media_files.delete
```

Configure them under `authorization.permissions`.

## Laravel Gate example

```php
use Illuminate\Support\Facades\Gate;

Gate::define('media_files.view-any', fn ($user) => $user->isAdmin());
Gate::define('media_files.create', fn ($user) => $user->isAdmin());
Gate::define('media_files.update', fn ($user) => $user->isAdmin());
Gate::define('media_files.delete', fn ($user) => $user->isAdmin());
```

If your ACL registers a `Gate::before()` callback, including Spatie-style or custom role/permission systems, the package uses that result through Laravel Gate.

## Disable authorization

For a trusted internal application that handles access at a higher layer:

```php
'authorization' => [
    'enabled' => false,
    // ...
],
```

This makes all package actions pass their internal authorization checks. Do not disable this on a panel that can be reached by untrusted users unless equivalent authorization exists elsewhere.
