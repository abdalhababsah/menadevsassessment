<?php

namespace App\Actions\Roles;

use App\Services\AuditLogger;
use Spatie\Permission\Models\Role;

final class CloneRoleAction
{
    public function __construct(
        private AuditLogger $audit,
    ) {}

    public function handle(Role $sourceRole, string $newName): Role
    {
        /** @var Role $clone */
        $clone = Role::create(['name' => $newName, 'guard_name' => 'web']);
        $clone->syncPermissions($sourceRole->permissions);

        $this->audit->log('role.cloned', $clone, [
            'source_role' => $sourceRole->name,
            'new_name' => $newName,
        ]);

        return $clone;
    }
}
