<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * The complete permission catalog grouped by domain.
     *
     * @var array<string, array<int, string>>
     */
    public const PERMISSIONS = [
        'Quizzes' => [
            'quiz.view',
            'quiz.create',
            'quiz.edit',
            'quiz.delete',
            'quiz.publish',
            'quiz.duplicate',
        ],
        'Question Bank' => [
            'questionbank.view',
            'questionbank.create',
            'questionbank.edit',
            'questionbank.delete',
            'questionbank.import',
            'questionbank.export',
        ],
        'Invitations' => [
            'invite.view',
            'invite.create',
            'invite.revoke',
        ],
        'Candidates' => [
            'candidate.view',
            'candidate.delete',
            'candidate.export',
        ],
        'Results' => [
            'results.view',
            'results.export',
            'results.viewSuspicious',
            'results.viewSnapshots',
        ],
        'RLHF' => [
            'rlhf.view',
            'rlhf.score',
            'rlhf.finalize',
        ],
        'Coding' => [
            'coding.view',
            'coding.rerun',
            'coding.override',
        ],
        'Users & Roles' => [
            'users.view',
            'users.invite',
            'users.edit',
            'users.deactivate',
            'roles.view',
            'roles.manage',
        ],
        'System' => [
            'system.settings',
            'system.auditLog',
            'system.integrations',
        ],
    ];

    public function run(): void
    {
        $allPermissions = collect(self::PERMISSIONS)->flatten();

        $allPermissions->each(function (string $permission) {
            Permission::findOrCreate($permission, 'web');
        });

        $this->command->info("Seeded {$allPermissions->count()} permissions.");
    }
}
