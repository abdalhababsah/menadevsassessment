<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class AuditorRoleSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::findOrCreate('Auditor', 'web');

        $role->syncPermissions([
            'quiz.view',
            'questionbank.view',
            'candidate.view',
            'results.view',
            'results.export',
            'results.viewSuspicious',
            'results.viewSnapshots',
            'users.view',
            'roles.view',
            'system.auditLog',
        ]);

        $this->command->info('Auditor role seeded with '.$role->permissions->count().' permissions.');
    }
}
