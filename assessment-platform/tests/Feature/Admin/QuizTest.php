<?php

use App\Enums\InvitationStatus;
use App\Enums\QuizNavigationMode;
use App\Enums\QuizStatus;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizInvitation;
use App\Models\QuizSection;

describe('quiz publish flow', function () {
    test('quiz is created as draft by default', function () {
        $quiz = Quiz::factory()->create();

        expect($quiz->status)->toBe(QuizStatus::Draft);
    });

    test('quiz can be published', function () {
        $quiz = Quiz::factory()->create();
        $quiz->update(['status' => QuizStatus::Published]);

        expect($quiz->fresh()->status)->toBe(QuizStatus::Published);
    });

    test('scopePublished returns only published quizzes', function () {
        Quiz::factory()->create(); // draft
        Quiz::factory()->published()->create();
        Quiz::factory()->archived()->create();

        expect(Quiz::published()->count())->toBe(1);
    });

    test('scopeActive returns published quizzes within time window', function () {
        Quiz::factory()->published()->withTimeWindow()->create(); // active
        Quiz::factory()->published()->create(); // no time window = active
        Quiz::factory()->expired()->create(); // published but ended
        Quiz::factory()->create(); // draft

        expect(Quiz::active()->count())->toBe(2);
    });

    test('quiz casts navigation_mode to enum', function () {
        $quiz = Quiz::factory()->forwardOnly()->create();

        expect($quiz->navigation_mode)->toBe(QuizNavigationMode::ForwardOnly);
    });

    test('quiz casts boolean flags correctly', function () {
        $quiz = Quiz::factory()->withAntiCheat()->create();

        expect($quiz->camera_enabled)->toBeTrue()
            ->and($quiz->anti_cheat_enabled)->toBeTrue()
            ->and($quiz->randomize_questions)->toBeFalse();
    });
});

describe('section ordering', function () {
    test('sections are returned ordered by position', function () {
        $quiz = Quiz::factory()->create();

        QuizSection::factory()->create(['quiz_id' => $quiz->id, 'title' => 'Third', 'position' => 2]);
        QuizSection::factory()->create(['quiz_id' => $quiz->id, 'title' => 'First', 'position' => 0]);
        QuizSection::factory()->create(['quiz_id' => $quiz->id, 'title' => 'Second', 'position' => 1]);

        $sections = $quiz->sections;

        expect($sections)->toHaveCount(3)
            ->and($sections[0]->title)->toBe('First')
            ->and($sections[1]->title)->toBe('Second')
            ->and($sections[2]->title)->toBe('Third');
    });

    test('section questions are attached with pivot data', function () {
        $section = QuizSection::factory()->create();
        $question = Question::factory()->create();

        $section->questions()->attach($question->id, [
            'question_version' => 2,
            'points_override' => 5.50,
            'time_limit_override_seconds' => 120,
            'position' => 0,
        ]);

        $loaded = $section->questions()->first();

        expect($loaded->pivot->question_version)->toBe(2)
            ->and($loaded->pivot->points_override)->toBe('5.50')
            ->and($loaded->pivot->time_limit_override_seconds)->toBe(120)
            ->and($loaded->pivot->position)->toBe(0);
    });

    test('section questions are ordered by pivot position', function () {
        $section = QuizSection::factory()->create();
        $q1 = Question::factory()->create();
        $q2 = Question::factory()->create();
        $q3 = Question::factory()->create();

        $section->questions()->attach($q1->id, ['position' => 2, 'question_version' => 1]);
        $section->questions()->attach($q2->id, ['position' => 0, 'question_version' => 1]);
        $section->questions()->attach($q3->id, ['position' => 1, 'question_version' => 1]);

        $questions = $section->questions;

        expect($questions[0]->id)->toBe($q2->id)
            ->and($questions[1]->id)->toBe($q3->id)
            ->and($questions[2]->id)->toBe($q1->id);
    });
});

describe('invitation logic', function () {
    test('invitation generates token on creation', function () {
        $invitation = QuizInvitation::factory()->create();

        expect($invitation->token)
            ->toBeString()
            ->toHaveLength(64);
    });

    test('invitation tokens are unique', function () {
        $tokens = collect(range(1, 20))
            ->map(fn () => QuizInvitation::factory()->create()->token);

        expect($tokens->unique()->count())->toBe(20);
    });

    test('invitation without expiry or limits is usable', function () {
        $invitation = QuizInvitation::factory()->create();

        expect($invitation->isUsable())->toBeTrue()
            ->and($invitation->isExpired())->toBeFalse()
            ->and($invitation->isExhausted())->toBeFalse()
            ->and($invitation->isRevoked())->toBeFalse()
            ->and($invitation->status())->toBe(InvitationStatus::Active);
    });

    test('expired invitation is not usable', function () {
        $invitation = QuizInvitation::factory()->expired()->create();

        expect($invitation->isExpired())->toBeTrue()
            ->and($invitation->isUsable())->toBeFalse()
            ->and($invitation->status())->toBe(InvitationStatus::Expired);
    });

    test('exhausted invitation is not usable', function () {
        $invitation = QuizInvitation::factory()->exhausted()->create();

        expect($invitation->isExhausted())->toBeTrue()
            ->and($invitation->isUsable())->toBeFalse()
            ->and($invitation->status())->toBe(InvitationStatus::Exhausted);
    });

    test('revoked invitation is not usable', function () {
        $invitation = QuizInvitation::factory()->revoked()->create();

        expect($invitation->isRevoked())->toBeTrue()
            ->and($invitation->isUsable())->toBeFalse()
            ->and($invitation->status())->toBe(InvitationStatus::Revoked);
    });

    test('invitation with remaining uses is usable', function () {
        $invitation = QuizInvitation::factory()->limited(10)->create([
            'uses_count' => 5,
        ]);

        expect($invitation->isUsable())->toBeTrue()
            ->and($invitation->isExhausted())->toBeFalse();
    });

    test('invitation with no max_uses is never exhausted', function () {
        $invitation = QuizInvitation::factory()->create([
            'uses_count' => 1000,
        ]);

        expect($invitation->isExhausted())->toBeFalse()
            ->and($invitation->isUsable())->toBeTrue();
    });

    test('revoked status takes priority over expired', function () {
        $invitation = QuizInvitation::factory()->expired()->revoked()->create();

        expect($invitation->status())->toBe(InvitationStatus::Revoked);
    });
});
