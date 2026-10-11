<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\User;

class UserPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole(RoleName::SuperAdmin->value)) {
            if (in_array($ability, ['delete', 'forceDelete'], true)) {
                return null; // biarkan method delete mengecek ! $user->is($record)
            }
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([RoleName::Admin->value, RoleName::SuperAdmin->value]);
    }

    public function view(User $user, User $record): bool
    {
        return $user->hasAnyRole([RoleName::Admin->value, RoleName::SuperAdmin->value]);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([RoleName::Admin->value, RoleName::SuperAdmin->value]);
    }

    public function update(User $user, User $record): bool
    {
        return $user->hasAnyRole([RoleName::Admin->value, RoleName::SuperAdmin->value]);
    }

    public function delete(User $user, User $record): bool
    {
        return $user->hasAnyRole([RoleName::Admin->value, RoleName::SuperAdmin->value]) && ! $user->is($record);
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasAnyRole([RoleName::Admin->value, RoleName::SuperAdmin->value]);
    }

    public function restore(User $user, User $record): bool
    {
        return $user->hasAnyRole([RoleName::Admin->value, RoleName::SuperAdmin->value]);
    }

    public function restoreAny(User $user): bool
    {
        return $user->hasAnyRole([RoleName::Admin->value, RoleName::SuperAdmin->value]);
    }

    public function forceDelete(User $user, User $record): bool
    {
        return $user->hasAnyRole([RoleName::Admin->value, RoleName::SuperAdmin->value]) && ! $user->is($record);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->hasAnyRole([RoleName::Admin->value, RoleName::SuperAdmin->value]);
    }
}
