<?php

namespace Sarmadict\FilamentMedia\Services;

use Illuminate\Support\Facades\Gate;

class AuthorizationManager
{
    public function allows(string $action): bool
    {
        if (! (bool) config('filament-media.authorization.enabled', true)) {
            return true;
        }

        $permission = config("filament-media.authorization.permissions.{$action}");

        if (! is_string($permission) || $permission === '') {
            return false;
        }

        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        return Gate::forUser($user)->allows($permission);
    }

    public function authorize(string $action): void
    {
        abort_unless($this->allows($action), 403);
    }
}
