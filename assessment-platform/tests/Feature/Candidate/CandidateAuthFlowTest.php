<?php

use App\Models\Candidate;
use App\Models\CandidateEmailVerification;
use App\Models\Quiz;
use App\Models\QuizInvitation;
use App\Notifications\SendCandidateVerificationEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

describe('invitation landing', function () {
    test('shows email entry page for valid invitation', function () {
        $quiz = Quiz::factory()->create(['title' => 'My Quiz']);
        $invitation = QuizInvitation::factory()->create(['quiz_id' => $quiz->id]);

        $this->get('/i/'.$invitation->token)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Candidate/Invitation/EmailEntry')
                ->where('invitation.quiz.title', 'My Quiz')
                ->where('invitation.token', $invitation->token)
            );

        expect(session('quiz_invitation_token'))->toBe($invitation->token);
    });

    test('rejects expired invitation', function () {
        $invitation = QuizInvitation::factory()->expired()->create();

        $this->get('/i/'.$invitation->token)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Candidate/InvitationError')
                ->where('reason', 'expired')
            );
    });

    test('rejects revoked invitation', function () {
        $invitation = QuizInvitation::factory()->revoked()->create();

        $this->get('/i/'.$invitation->token)
            ->assertInertia(fn ($page) => $page
                ->component('Candidate/InvitationError')
                ->where('reason', 'revoked')
            );
    });

    test('rejects invalid token', function () {
        $this->get('/i/'.str_repeat('z', 64))
            ->assertInertia(fn ($page) => $page
                ->component('Candidate/InvitationError')
                ->where('reason', 'not_found')
            );
    });

    test('redirects authenticated verified candidate straight to pre-quiz', function () {
        $invitation = QuizInvitation::factory()->create();
        $candidate = Candidate::factory()->verified()->create();

        Auth::guard('candidate')->login($candidate);

        $this->get('/i/'.$invitation->token)
            ->assertRedirect(route('candidate.pre-quiz'));
    });
});

describe('guest email submission flow', function () {
    test('creates a guest candidate and dispatches verification email', function () {
        Notification::fake();

        $invitation = QuizInvitation::factory()->create();

        // Visit the invitation first to stash the token in session.
        $this->get('/i/'.$invitation->token);

        $this->post(route('candidate.email.submit'), ['email' => 'guest@example.com'])
            ->assertRedirect(route('candidate.check-email'));

        $candidate = Candidate::where('email', 'guest@example.com')->first();
        expect($candidate)
            ->not->toBeNull()
            ->is_guest->toBeTrue()
            ->email_verified_at->toBeNull();

        Notification::assertSentTo($candidate, SendCandidateVerificationEmail::class);
    });

    test('check-email page shows the candidate email', function () {
        $invitation = QuizInvitation::factory()->create();

        $this->get('/i/'.$invitation->token);

        $this->post(route('candidate.email.submit'), ['email' => 'flash@example.com']);

        // The redirect carries the email via flash; we follow it.
        $this->withSession([
            'check_email' => 'flash@example.com',
            'quiz_invitation_token' => $invitation->token,
        ])->get(route('candidate.check-email'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Candidate/Invitation/CheckEmail')
                ->where('email', 'flash@example.com')
            );
    });

    test('redirects to invitation if check-email visited without context', function () {
        $invitation = QuizInvitation::factory()->create();
        $this->withSession(['quiz_invitation_token' => $invitation->token])
            ->get(route('candidate.check-email'))
            ->assertRedirect(route('candidate.invitations.show', $invitation->token));
    });
});

describe('email verification flow', function () {
    test('verifies candidate via token and redirects to pre-quiz', function () {
        $invitation = QuizInvitation::factory()->create();
        $candidate = Candidate::factory()->guest()->create();
        $verification = $candidate->emailVerifications()->create([
            'token' => str_repeat('a', 64),
            'expires_at' => now()->addDay(),
        ]);

        $this->withSession(['quiz_invitation_token' => $invitation->token])
            ->get(route('candidate.verify-email', ['token' => $verification->token]))
            ->assertRedirect(route('candidate.pre-quiz'));

        expect($candidate->fresh()->hasVerifiedEmail())->toBeTrue();
        expect(Auth::guard('candidate')->check())->toBeTrue();
        expect(Auth::guard('candidate')->id())->toBe($candidate->id);
    });

    test('rejects invalid verification token', function () {
        $invitation = QuizInvitation::factory()->create();

        $this->withSession(['quiz_invitation_token' => $invitation->token])
            ->get(route('candidate.verify-email', ['token' => str_repeat('x', 64)]))
            ->assertRedirect(route('candidate.invitations.show', $invitation->token));

        expect(Auth::guard('candidate')->check())->toBeFalse();
    });

    test('rejects expired verification token', function () {
        $invitation = QuizInvitation::factory()->create();
        $candidate = Candidate::factory()->guest()->create();
        $verification = $candidate->emailVerifications()->create([
            'token' => str_repeat('b', 64),
            'expires_at' => now()->subDay(),
        ]);

        $this->withSession(['quiz_invitation_token' => $invitation->token])
            ->get(route('candidate.verify-email', ['token' => $verification->token]))
            ->assertRedirect(route('candidate.invitations.show', $invitation->token));
    });
});

describe('full registration flow', function () {
    test('registered candidate skips email verification entirely', function () {
        $invitation = QuizInvitation::factory()->create();

        $this->withSession(['quiz_invitation_token' => $invitation->token])
            ->post(route('candidate.register'), [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'password' => 'secret-password-1',
                'password_confirmation' => 'secret-password-1',
            ])
            ->assertRedirect(route('candidate.pre-quiz'));

        $candidate = Candidate::where('email', 'jane@example.com')->first();
        expect($candidate)
            ->not->toBeNull()
            ->is_guest->toBeFalse()
            ->and($candidate->hasVerifiedEmail())->toBeTrue();

        expect(Auth::guard('candidate')->id())->toBe($candidate->id);

        // No verification record should have been created.
        expect(CandidateEmailVerification::where('candidate_id', $candidate->id)->count())->toBe(0);
    });

    test('register page renders for valid invitation', function () {
        $invitation = QuizInvitation::factory()->create();

        $this->withSession(['quiz_invitation_token' => $invitation->token])
            ->get(route('candidate.register'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Candidate/Invitation/Register')
                ->where('invitation.token', $invitation->token)
            );
    });
});

describe('email domain restriction', function () {
    test('rejects email outside the restricted domain', function () {
        Notification::fake();

        $invitation = QuizInvitation::factory()->domainRestricted('company.com')->create();
        $this->get('/i/'.$invitation->token);

        $this->post(route('candidate.email.submit'), ['email' => 'guest@gmail.com'])
            ->assertSessionHasErrors('email');

        expect(Candidate::where('email', 'guest@gmail.com')->exists())->toBeFalse();
        Notification::assertNothingSent();
    });

    test('accepts email matching the restricted domain', function () {
        Notification::fake();

        $invitation = QuizInvitation::factory()->domainRestricted('company.com')->create();
        $this->get('/i/'.$invitation->token);

        $this->post(route('candidate.email.submit'), ['email' => 'employee@company.com'])
            ->assertRedirect(route('candidate.check-email'));

        expect(Candidate::where('email', 'employee@company.com')->exists())->toBeTrue();
    });

    test('domain restriction is case-insensitive', function () {
        Notification::fake();

        $invitation = QuizInvitation::factory()->domainRestricted('Company.COM')->create();
        $this->get('/i/'.$invitation->token);

        $this->post(route('candidate.email.submit'), ['email' => 'employee@COMPANY.com'])
            ->assertRedirect(route('candidate.check-email'));
    });

    test('register flow enforces domain restriction', function () {
        $invitation = QuizInvitation::factory()->domainRestricted('company.com')->create();

        $this->withSession(['quiz_invitation_token' => $invitation->token])
            ->post(route('candidate.register'), [
                'name' => 'Outsider',
                'email' => 'outsider@gmail.com',
                'password' => 'secret-password-1',
                'password_confirmation' => 'secret-password-1',
            ])
            ->assertSessionHasErrors('email');
    });
});

describe('pre-quiz page', function () {
    test('requires candidate authentication', function () {
        $this->get(route('candidate.pre-quiz'))
            ->assertRedirect();
    });

    test('renders for authenticated candidate with active invitation', function () {
        $quiz = Quiz::factory()->create([
            'title' => 'Final Exam',
            'time_limit_seconds' => 1800,
            'camera_enabled' => true,
            'anti_cheat_enabled' => true,
        ]);
        $invitation = QuizInvitation::factory()->create(['quiz_id' => $quiz->id]);
        $candidate = Candidate::factory()->verified()->create();

        Auth::guard('candidate')->login($candidate);

        $this->withSession(['quiz_invitation_token' => $invitation->token])
            ->get(route('candidate.pre-quiz'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Candidate/PreQuiz')
                ->where('quiz.title', 'Final Exam')
                ->where('quiz.time_limit_seconds', 1800)
                ->where('quiz.camera_enabled', true)
                ->where('quiz.anti_cheat_enabled', true)
                ->where('invitation_token', $invitation->token)
            );
    });

    test('redirects when invitation no longer usable', function () {
        $invitation = QuizInvitation::factory()->revoked()->create();
        $candidate = Candidate::factory()->verified()->create();

        Auth::guard('candidate')->login($candidate);

        $this->withSession(['quiz_invitation_token' => $invitation->token])
            ->get(route('candidate.pre-quiz'))
            ->assertRedirect(route('candidate.invitations.show', $invitation->token));
    });
});

describe('end-to-end guest flow', function () {
    test('full flow: visit invite, submit email, verify, see pre-quiz', function () {
        Notification::fake();

        $quiz = Quiz::factory()->create(['title' => 'Onboarding Test']);
        $invitation = QuizInvitation::factory()->create(['quiz_id' => $quiz->id]);

        // 1. Land on the invitation
        $this->get('/i/'.$invitation->token)->assertOk();

        // 2. Submit email
        $this->post(route('candidate.email.submit'), ['email' => 'newbie@example.com'])
            ->assertRedirect(route('candidate.check-email'));

        $candidate = Candidate::where('email', 'newbie@example.com')->first();
        expect($candidate)->not->toBeNull();

        Notification::assertSentTo($candidate, SendCandidateVerificationEmail::class);

        // 3. Click the verification link from the email
        /** @var CandidateEmailVerification $verification */
        $verification = $candidate->emailVerifications()->latest('id')->first();

        $this->get(route('candidate.verify-email', ['token' => $verification->token]))
            ->assertRedirect(route('candidate.pre-quiz'));

        // 4. Land on pre-quiz
        $this->get(route('candidate.pre-quiz'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Candidate/PreQuiz')
                ->where('quiz.title', 'Onboarding Test')
                ->where('candidate.email', 'newbie@example.com')
            );

        expect(Auth::guard('candidate')->check())->toBeTrue();
    });
});
