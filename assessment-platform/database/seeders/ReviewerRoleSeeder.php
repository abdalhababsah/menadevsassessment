<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class ReviewerRoleSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::findOrCreate('Reviewer', 'web');

        $role->syncPermissions([
            'quiz.view',
            'questionbank.view',
            'results.view',
            'results.export',
            'rlhf.view',
            'rlhf.score',
            'rlhf.finalize',
            'coding.view',
            'coding.rerun',
            'coding.override',
        ]);

        $this->command->info('Reviewer role seeded with '.$role->permissions->count().' permissions.');
    }
}
