<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class QuizAuthorRoleSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::findOrCreate('Quiz Author', 'web');

        $role->syncPermissions([
            'quiz.view',
            'quiz.create',
            'quiz.edit',
            'quiz.delete',
            'quiz.duplicate',
            'questionbank.view',
            'questionbank.create',
            'questionbank.edit',
            'questionbank.delete',
            'questionbank.import',
            'questionbank.export',
            'invite.view',
            'invite.create',
            'results.view',
        ]);

        $this->command->info('Quiz Author role seeded with '.$role->permissions->count().' permissions.');
    }
}
