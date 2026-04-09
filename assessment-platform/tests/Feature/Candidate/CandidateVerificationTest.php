<?php

use App\Actions\Candidates\CreateGuestCandidateAction;
use App\Actions\Candidates\RegisterCandidateAction;
use App\Actions\Candidates\VerifyCandidateEmailAction;
use App\Exceptions\VerificationException;
use App\Models\Candidate;
use App\Models\CandidateEmailVerification;
use App\Notifications\SendCandidateVerificationEmail;
use Illuminate\Support\Facades\Notification;

describe('guest creation flow', function () {
    test('creates a guest candidate with unverified email', function () {
        Notification::fake();

        $action = app(CreateGuestCandidateAction::class);
        $candidate = $action->handle('guest@example.com');

        expect($candidate)
            ->email->toBe('guest@example.com')
            ->is_guest->toBeTrue()
            ->email_verified_at->toBeNull();

        $this->assertDatabaseHas('candidates', [
            'email' => 'guest@example.com',
            'is_guest' => true,
        ]);
    });

    test('creates a verification token on guest creation', function () {
        Notification::fake();

        $action = app(CreateGuestCandidateAction::class);
        $candidate = $action->handle('guest@example.com');

        expect($candidate->emailVerifications)->toHaveCount(1);

        $verification = $candidate->emailVerifications->first();

        expect($verification)
            ->token->toHaveLength(64)
            ->expires_at->toBeGreaterThan(now())
            ->consumed_at->toBeNull();
    });

    test('dispatches verification email notification', function () {
        Notification::fake();

        $action = app(CreateGuestCandidateAction::class);
        $candidate = $action->handle('guest@example.com');

        Notification::assertSentTo($candidate, SendCandidateVerificationEmail::class);
    });

    test('reuses existing candidate on duplicate email', function () {
        Notification::fake();

        $action = app(CreateGuestCandidateAction::class);
        $first = $action->handle('guest@example.com');
        $second = $action->handle('guest@example.com');

        expect($first->id)->toBe($second->id);
        expect(Candidate::where('email', 'guest@example.com')->count())->toBe(1);

        // But two verification tokens should exist
        expect(CandidateEmailVerification::where('candidate_id', $first->id)->count())->toBe(2);
    });
});

describe('verification token', function () {
    test('verifies candidate email with valid token', function () {
        $candidate = Candidate::factory()->guest()->create();
        $verification = $candidate->emailVerifications()->create([
            'token' => str_repeat('a', 64),
            'expires_at' => now()->addHours(24),
        ]);

        $action = app(VerifyCandidateEmailAction::class);
        $verified = $action->handle($verification->token);

        expect($verified)
            ->id->toBe($candidate->id)
            ->email_verified_at->not->toBeNull();

        $verification->refresh();
        expect($verification->consumed_at)->not->toBeNull();
    });

    test('rejects expired token', function () {
        $candidate = Candidate::factory()->guest()->create();
        $verification = $candidate->emailVerifications()->create([
            'token' => str_repeat('b', 64),
            'expires_at' => now()->subHour(),
        ]);

        $action = app(VerifyCandidateEmailAction::class);

        expect(fn () => $action->handle($verification->token))
            ->toThrow(VerificationException::class, 'The verification token has expired.');
    });

    test('rejects already consumed token', function () {
        $candidate = Candidate::factory()->guest()->create();
        $verification = $candidate->emailVerifications()->create([
            'token' => str_repeat('c', 64),
            'expires_at' => now()->addHours(24),
            'consumed_at' => now(),
        ]);

        $action = app(VerifyCandidateEmailAction::class);

        expect(fn () => $action->handle($verification->token))
            ->toThrow(VerificationException::class, 'This email has already been verified.');
    });

    test('rejects invalid token', function () {
        $action = app(VerifyCandidateEmailAction::class);

        expect(fn () => $action->handle('nonexistent-token'))
            ->toThrow(VerificationException::class, 'The verification token is invalid.');
    });
});

describe('full registration flow', function () {
    test('registers a candidate with name, email, and password', function () {
        $action = app(RegisterCandidateAction::class);
        $candidate = $action->handle('John Doe', 'john@example.com', 'securepassword');

        expect($candidate)
            ->name->toBe('John Doe')
            ->email->toBe('john@example.com')
            ->is_guest->toBeFalse()
            ->email_verified_at->not->toBeNull();

        // Password should be hashed, not stored raw
        expect($candidate->password)->not->toBe('securepassword');

        $this->assertDatabaseHas('candidates', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'is_guest' => false,
        ]);
    });

    test('registered candidate has verified email immediately', function () {
        $action = app(RegisterCandidateAction::class);
        $candidate = $action->handle('Jane', 'jane@example.com', 'password123');

        expect($candidate->hasVerifiedEmail())->toBeTrue();
    });
});

describe('candidate auth guard', function () {
    test('candidate guard is configured', function () {
        $config = config('auth.guards.candidate');

        expect($config)
            ->toBeArray()
            ->and($config['driver'])->toBe('session')
            ->and($config['provider'])->toBe('candidates');
    });

    test('candidate provider uses Candidate model', function () {
        $config = config('auth.providers.candidates');

        expect($config)
            ->toBeArray()
            ->and($config['driver'])->toBe('eloquent')
            ->and($config['model'])->toBe(Candidate::class);
    });
});
