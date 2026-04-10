<?php

namespace App\Actions\Roles;

use App\Services\AuditLogger;
use Spatie\Permission\Models\Role;

final class UpdateRoleAction
{
    public function __construct(
        private AuditLogger $audit,
    ) {}

    /**
     * @param  array<int, string>  $permissions
     */
    public function handle(Role $role, string $name, array $permissions): Role
    {
        $oldPermissions = $role->permissions->pluck('name')->toArray();

        $role->update(['name' => $name]);
        $role->syncPermissions($permissions);

        $this->audit->log('role.updated', $role, [
            'name' => ['old' => $role->getOriginal('name'), 'new' => $name],
            'permissions' => ['old' => $oldPermissions, 'new' => $permissions],
        ]);

        return $role->refresh();
    }
}
