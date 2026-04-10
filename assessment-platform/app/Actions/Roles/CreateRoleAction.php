<?php

namespace App\Actions\Roles;

use App\Services\AuditLogger;
use Spatie\Permission\Models\Role;

final class CreateRoleAction
{
    public function __construct(
        private AuditLogger $audit,
    ) {}

    /**
     * @param  array<int, string>  $permissions
     */
    public function handle(string $name, array $permissions): Role
    {
        /** @var Role $role */
        $role = Role::create(['name' => $name, 'guard_name' => 'web']);
        $role->syncPermissions($permissions);

        $this->audit->log('role.created', $role, [
            'name' => $name,
            'permissions' => $permissions,
        ]);

        return $role;
    }
}
