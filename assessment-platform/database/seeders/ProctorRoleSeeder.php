<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class ProctorRoleSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::findOrCreate('Proctor', 'web');

        $role->syncPermissions([
            'quiz.view',
            'candidate.view',
            'results.view',
            'results.viewSuspicious',
            'results.viewSnapshots',
        ]);

        $this->command->info('Proctor role seeded with '.$role->permissions->count().' permissions.');
    }
}
