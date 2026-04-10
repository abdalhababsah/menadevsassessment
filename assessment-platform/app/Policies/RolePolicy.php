<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

final class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_super_admin || $user->hasPermissionTo('roles.manage');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->is_super_admin || $user->hasPermissionTo('roles.manage');
    }

    public function create(User $user): bool
    {
        return $user->is_super_admin || $user->hasPermissionTo('roles.manage');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->is_super_admin || $user->hasPermissionTo('roles.manage');
    }

    public function delete(User $user, Role $role): bool
    {
        if ($role->name === 'Super Admin') {
            return false;
        }

        return $user->is_super_admin || $user->hasPermissionTo('roles.manage');
    }
}
