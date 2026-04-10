<?php

namespace App\Actions\Roles;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;

final class DeleteRoleAction
{
    public function __construct(
        private AuditLogger $audit,
    ) {}

    public function handle(Role $role, Role $replacementRole): void
    {
        /** @var Collection<int, User> $usersWithRole */
        $usersWithRole = $role->users;

        foreach ($usersWithRole as $user) {
            /** @var User $user */
            $user->removeRole($role);
            $user->assignRole($replacementRole);
        }

        $this->audit->log('role.deleted', $role, [
            'name' => $role->name,
            'replacement_role' => $replacementRole->name,
            'users_reassigned' => $usersWithRole->count(),
        ]);

        $role->delete();
    }
}
