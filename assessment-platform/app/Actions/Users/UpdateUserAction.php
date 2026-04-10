<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Services\AuditLogger;

final class UpdateUserAction
{
    public function __construct(
        private AuditLogger $audit,
    ) {}

    /**
     * @param  array<int, string>  $roles
     */
    public function handle(User $user, string $name, array $roles): User
    {
        $oldRoles = $user->getRoleNames()->toArray();
        $oldName = $user->name;

        $user->update(['name' => $name]);
        $user->syncRoles($roles);

        $this->audit->log('user.updated', $user, [
            'name' => ['old' => $oldName, 'new' => $name],
            'roles' => ['old' => $oldRoles, 'new' => $roles],
        ]);

        return $user->refresh();
    }
}
