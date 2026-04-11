<?php

use App\Actions\Attempts\LockOrAutoSubmitAttemptAction;
use App\Actions\Attempts\SubmitQuizAttemptAction;
use App\Enums\AttemptStatus;
use App\Models\AuditLog;
use App\Models\QuizAttempt;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

function makeAuditor(array $permissions): User
{
    $user = User::factory()->create();
    $role = Role::findOrCreate('Auditor '.uniqid(), 'web');
    $role->syncPermissions($permissions);
    $user->assignRole($role);

    return $user;
}

describe('audit log index permissions', function () {
    test('index is blocked without system.auditLog', function () {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/audit-log')->assertForbidden();
    });

    test('index renders for users with system.auditLog', function () {
        $user = makeAuditor(['system.auditLog']);
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'quiz.created',
            'auditable_type' => 'App\\Models\\Quiz',
            'auditable_id' => 1,
            'changes' => ['title' => 'Sample'],
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/admin/audit-log')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/AuditLog/Index')
                ->has('logs', 1)
                ->where('logs.0.action', 'quiz.created')
            );
    });
});

describe('audit log filters', function () {
    beforeEach(function () {
        $this->auditor = makeAuditor(['system.auditLog']);
        $this->actor1 = User::factory()->create(['name' => 'Alice']);
        $this->actor2 = User::factory()->create(['name' => 'Bob']);

        AuditLog::create([
            'user_id' => $this->actor1->id,
            'action' => 'quiz.created',
            'auditable_type' => 'App\\Models\\Quiz',
            'auditable_id' => 1,
            'changes' => ['title' => 'Alpha'],
            'ip_address' => '10.0.0.1',
            'created_at' => now()->subDays(2),
        ]);

        AuditLog::create([
            'user_id' => $this->actor1->id,
            'action' => 'quiz.published',
            'auditable_type' => 'App\\Models\\Quiz',
            'auditable_id' => 1,
            'changes' => null,
            'ip_address' => '10.0.0.1',
            'created_at' => now()->subDay(),
        ]);

        AuditLog::create([
            'user_id' => $this->actor2->id,
            'action' => 'user.updated',
            'auditable_type' => 'App\\Models\\User',
            'auditable_id' => $this->actor1->id,
            'changes' => ['name' => ['old' => 'X', 'new' => 'Y']],
            'ip_address' => '10.0.0.2',
            'created_at' => now(),
        ]);
    });

    test('filter by actor_id returns only that actor', function () {
        $this->actingAs($this->auditor)
            ->get('/admin/audit-log?actor_id='.$this->actor1->id)
            ->assertInertia(fn ($page) => $page->has('logs', 2));
    });

    test('filter by action prefix works', function () {
        $this->actingAs($this->auditor)
            ->get('/admin/audit-log?action=quiz.')
            ->assertInertia(fn ($page) => $page->has('logs', 2));
    });

    test('filter by auditable_type substring works', function () {
        $this->actingAs($this->auditor)
            ->get('/admin/audit-log?auditable_type=User')
            ->assertInertia(fn ($page) => $page
                ->has('logs', 1)
                ->where('logs.0.action', 'user.updated')
            );
    });

    test('filter by date range honors from/to', function () {
        // Only the most recent log was created at `now()` — the older two are
        // more than 12 hours old. `from` = now minus 12 hours should include
        // exactly one entry.
        $from = now()->subHours(12)->toDateTimeString();

        $this->actingAs($this->auditor)
            ->get('/admin/audit-log?from='.$from)
            ->assertInertia(fn ($page) => $page->has('logs', 1));
    });
});

describe('audit log show', function () {
    test('detail view renders JSON diff', function () {
        $user = makeAuditor(['system.auditLog']);
        $log = AuditLog::create([
            'user_id' => $user->id,
            'action' => 'coding.override',
            'auditable_type' => 'App\\Models\\AttemptAnswer',
            'auditable_id' => 42,
            'changes' => [
                'previous_reviewer_score' => 5.0,
                'new_reviewer_score' => 8.5,
                'reason' => 'Partial credit',
            ],
            'ip_address' => '10.0.0.5',
            'created_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/admin/audit-log/'.$log->id)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/AuditLog/Show')
                ->where('log.id', $log->id)
                ->where('log.action', 'coding.override')
                ->where('log.changes.new_reviewer_score', 8.5)
                ->where('log.auditable_type_short', 'AttemptAnswer')
            );
    });

    test('show is blocked without permission', function () {
        $user = User::factory()->create();
        $log = AuditLog::create([
            'user_id' => null,
            'action' => 'test',
            'auditable_type' => 'x',
            'auditable_id' => 1,
            'created_at' => now(),
        ]);

        $this->actingAs($user)->get('/admin/audit-log/'.$log->id)->assertForbidden();
    });
});

describe('action classes write audit entries', function () {
    test('SubmitQuizAttemptAction writes a quiz.attempt_submitted entry', function () {
        $attempt = QuizAttempt::factory()->create([
            'status' => AttemptStatus::InProgress,
        ]);

        app(SubmitQuizAttemptAction::class)->handle($attempt);

        $log = AuditLog::query()->where('action', 'quiz.attempt_submitted')->latest('id')->first();
        expect($log)->not->toBeNull()
            ->and($log->auditable_type)->toBe('App\\Models\\QuizAttempt')
            ->and($log->auditable_id)->toBe($attempt->id);
    });

    test('LockOrAutoSubmitAttemptAction writes a quiz.attempt_locked entry', function () {
        $attempt = QuizAttempt::factory()->create([
            'status' => AttemptStatus::InProgress,
        ]);

        app(LockOrAutoSubmitAttemptAction::class)->handle($attempt);

        $log = AuditLog::query()->where('action', 'quiz.attempt_locked')->latest('id')->first();
        expect($log)->not->toBeNull()
            ->and($log->changes['reason'])->toBe('anti_cheat_threshold_exceeded');
    });
});
