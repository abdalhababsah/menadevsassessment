<?php

use App\Models\AuditLog;
use App\Models\Quiz;
use App\Models\QuizInvitation;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->admin = User::factory()->superAdmin()->create();
});

describe('admin: list invitations', function () {
    test('renders invitations index for a quiz', function () {
        $quiz = Quiz::factory()->create();
        QuizInvitation::factory()->count(3)->create(['quiz_id' => $quiz->id]);

        $this->actingAs($this->admin)
            ->get(route('admin.quizzes.invitations.index', $quiz))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Quizzes/Edit/Invitations')
                ->has('invitations', 3)
                ->where('quiz.title', $quiz->title)
            );
    });

    test('serializes invitation with public_url and status', function () {
        $quiz = Quiz::factory()->create();
        $invitation = QuizInvitation::factory()->create(['quiz_id' => $quiz->id]);

        $this->actingAs($this->admin)
            ->get(route('admin.quizzes.invitations.index', $quiz))
            ->assertInertia(fn ($page) => $page
                ->where('invitations.0.token', $invitation->token)
                ->where('invitations.0.public_url', url("/i/{$invitation->token}"))
                ->where('invitations.0.status', 'active')
                ->where('invitations.0.is_usable', true)
            );
    });
});

describe('admin: create invitation', function () {
    test('creates an invitation with all fields', function () {
        $quiz = Quiz::factory()->create();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.quizzes.invitations.store', $quiz), [
                'max_uses' => 50,
                'expires_at' => now()->addDays(7)->toDateTimeString(),
                'email_domain_restriction' => 'example.com',
            ]);

        $response->assertSessionHasNoErrors()->assertRedirect();

        $invitation = $quiz->invitations()->latest('id')->first();
        expect($invitation)
            ->not->toBeNull()
            ->max_uses->toBe(50)
            ->email_domain_restriction->toBe('example.com')
            ->and(strlen($invitation->token))->toBe(64);
    });

    test('creates invitation without optional fields', function () {
        $quiz = Quiz::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.quizzes.invitations.store', $quiz), [])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $invitation = $quiz->invitations()->latest('id')->first();
        expect($invitation)
            ->not->toBeNull()
            ->max_uses->toBeNull()
            ->expires_at->toBeNull()
            ->email_domain_restriction->toBeNull();
    });

    test('rejects invalid email domain', function () {
        $quiz = Quiz::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.quizzes.invitations.store', $quiz), [
                'email_domain_restriction' => 'not-a-domain!!',
            ])
            ->assertSessionHasErrors('email_domain_restriction');
    });

    test('rejects expires_at in the past', function () {
        $quiz = Quiz::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.quizzes.invitations.store', $quiz), [
                'expires_at' => now()->subDay()->toDateTimeString(),
            ])
            ->assertSessionHasErrors('expires_at');
    });

    test('rejects max_uses below 1', function () {
        $quiz = Quiz::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.quizzes.invitations.store', $quiz), [
                'max_uses' => 0,
            ])
            ->assertSessionHasErrors('max_uses');
    });

    test('creating invitation creates audit log', function () {
        $quiz = Quiz::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.quizzes.invitations.store', $quiz), [
                'max_uses' => 10,
            ]);

        $log = AuditLog::where('action', 'quiz.invitation_created')->latest('id')->first();
        expect($log)->not->toBeNull()
            ->and($log->changes['max_uses'])->toBe(10);
    });

    test('generates unique tokens across many invitations', function () {
        $quiz = Quiz::factory()->create();

        $this->actingAs($this->admin);
        for ($i = 0; $i < 10; $i++) {
            $this->post(route('admin.quizzes.invitations.store', $quiz), []);
        }

        $tokens = QuizInvitation::pluck('token')->all();
        expect(count($tokens))->toBe(count(array_unique($tokens)));
        expect(count($tokens))->toBe(10);
    });
});

describe('admin: revoke invitation', function () {
    test('revokes an invitation', function () {
        $quiz = Quiz::factory()->create();
        $invitation = QuizInvitation::factory()->create(['quiz_id' => $quiz->id]);

        $this->actingAs($this->admin)
            ->delete(route('admin.quizzes.invitations.destroy', [$quiz, $invitation]))
            ->assertRedirect();

        $invitation->refresh();
        expect($invitation->revoked_at)->not->toBeNull();
        expect($invitation->isUsable())->toBeFalse();
    });

    test('revoke creates audit log', function () {
        $quiz = Quiz::factory()->create();
        $invitation = QuizInvitation::factory()->create(['quiz_id' => $quiz->id]);

        $this->actingAs($this->admin)
            ->delete(route('admin.quizzes.invitations.destroy', [$quiz, $invitation]));

        $log = AuditLog::where('action', 'quiz.invitation_revoked')->latest('id')->first();
        expect($log)->not->toBeNull();
    });

    test('cannot revoke an invitation belonging to a different quiz', function () {
        $quizA = Quiz::factory()->create();
        $quizB = Quiz::factory()->create();
        $invitation = QuizInvitation::factory()->create(['quiz_id' => $quizB->id]);

        $this->actingAs($this->admin)
            ->delete(route('admin.quizzes.invitations.destroy', [$quizA, $invitation]))
            ->assertNotFound();
    });
});

describe('admin: authorization', function () {
    test('user without invite.create cannot create invitations', function () {
        $user = User::factory()->create();
        $role = Role::findOrCreate('Quiz Editor', 'web');
        $role->syncPermissions(['quiz.view', 'quiz.edit']);
        $user->assignRole($role);

        $quiz = Quiz::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.quizzes.invitations.store', $quiz))
            ->assertForbidden();
    });

    test('user without invite.revoke cannot revoke invitations', function () {
        $user = User::factory()->create();
        $role = Role::findOrCreate('Quiz Editor 2', 'web');
        $role->syncPermissions(['quiz.view', 'quiz.edit', 'invite.create', 'invite.view']);
        $user->assignRole($role);

        $quiz = Quiz::factory()->create();
        $invitation = QuizInvitation::factory()->create(['quiz_id' => $quiz->id]);

        $this->actingAs($user)
            ->delete(route('admin.quizzes.invitations.destroy', [$quiz, $invitation]))
            ->assertForbidden();
    });

    test('user without invite.view cannot list invitations', function () {
        $user = User::factory()->create();
        $role = Role::findOrCreate('Quiz Editor 3', 'web');
        $role->syncPermissions(['quiz.view', 'quiz.edit']);
        $user->assignRole($role);

        $quiz = Quiz::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.quizzes.invitations.index', $quiz))
            ->assertForbidden();
    });
});

describe('public: invitation landing page', function () {
    test('shows landing for a usable invitation', function () {
        $quiz = Quiz::factory()->create();
        $invitation = QuizInvitation::factory()->create(['quiz_id' => $quiz->id]);

        $this->get('/i/'.$invitation->token)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Candidate/Invitation/EmailEntry')
                ->where('invitation.quiz.id', $quiz->id)
                ->where('invitation.token', $invitation->token)
            );
    });

    test('stashes the token in the session for later use', function () {
        $quiz = Quiz::factory()->create();
        $invitation = QuizInvitation::factory()->create(['quiz_id' => $quiz->id]);

        $this->get('/i/'.$invitation->token);

        expect(session('quiz_invitation_token'))->toBe($invitation->token);
    });

    test('renders not_found error for unknown token', function () {
        $this->get('/i/'.str_repeat('z', 64))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Candidate/InvitationError')
                ->where('reason', 'not_found')
            );
    });

    test('rejects expired invitation', function () {
        $quiz = Quiz::factory()->create();
        $invitation = QuizInvitation::factory()->expired()->create(['quiz_id' => $quiz->id]);

        $this->get('/i/'.$invitation->token)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Candidate/InvitationError')
                ->where('reason', 'expired')
            );
    });

    test('rejects exhausted invitation', function () {
        $quiz = Quiz::factory()->create();
        $invitation = QuizInvitation::factory()->exhausted()->create(['quiz_id' => $quiz->id]);

        $this->get('/i/'.$invitation->token)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Candidate/InvitationError')
                ->where('reason', 'exhausted')
            );
    });

    test('rejects revoked invitation', function () {
        $quiz = Quiz::factory()->create();
        $invitation = QuizInvitation::factory()->revoked()->create(['quiz_id' => $quiz->id]);

        $this->get('/i/'.$invitation->token)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Candidate/InvitationError')
                ->where('reason', 'revoked')
            );
    });
});
