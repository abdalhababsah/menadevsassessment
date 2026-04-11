<?php

use App\Enums\AttemptStatus;
use App\Enums\QuizNavigationMode;
use App\Events\QuizAttemptSubmitted;
use App\Models\Candidate;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizInvitation;
use App\Models\QuizSection;
use App\Models\QuizSectionQuestion;
use Illuminate\Support\Facades\Event;

/**
 * @return array{
 *     quiz: Quiz,
 *     section: QuizSection,
 *     singleQuestion: Question,
 *     singleCorrect: QuestionOption,
 *     multiQuestion: Question,
 *     multiCorrect: QuestionOption,
 * }
 */
function makeSubmissionQuiz(): array
{
    $quiz = Quiz::factory()->published()->create([
        'navigation_mode' => QuizNavigationMode::Free,
        'time_limit_seconds' => 600,
    ]);

    $section = QuizSection::factory()->create([
        'quiz_id' => $quiz->id,
        'title' => 'Section 1',
        'position' => 0,
    ]);

    $singleQuestion = Question::factory()->singleSelect()->create(['points' => 5]);
    $singleCorrect = QuestionOption::factory()->correct()->create([
        'question_id' => $singleQuestion->id,
        'position' => 0,
    ]);
    QuestionOption::factory()->create([
        'question_id' => $singleQuestion->id,
        'position' => 1,
    ]);

    $multiQuestion = Question::factory()->multiSelect()->create(['points' => 5]);
    $multiCorrect = QuestionOption::factory()->correct()->create([
        'question_id' => $multiQuestion->id,
        'position' => 0,
    ]);
    QuestionOption::factory()->create([
        'question_id' => $multiQuestion->id,
        'position' => 1,
    ]);

    QuizSectionQuestion::create([
        'quiz_section_id' => $section->id,
        'question_id' => $singleQuestion->id,
        'question_version' => 1,
        'position' => 0,
    ]);
    QuizSectionQuestion::create([
        'quiz_section_id' => $section->id,
        'question_id' => $multiQuestion->id,
        'question_version' => 1,
        'position' => 1,
    ]);

    return [
        'quiz' => $quiz,
        'section' => $section,
        'singleQuestion' => $singleQuestion,
        'singleCorrect' => $singleCorrect,
        'multiQuestion' => $multiQuestion,
        'multiCorrect' => $multiCorrect,
    ];
}

beforeEach(function () {
    $this->setup = makeSubmissionQuiz();
    $this->candidate = Candidate::factory()->verified()->create();
    $this->invitation = QuizInvitation::factory()->create(['quiz_id' => $this->setup['quiz']->id]);

    $this->actingAs($this->candidate, 'candidate')
        ->withSession(['quiz_invitation_token' => $this->invitation->token]);
});

describe('confirm-submit screen', function () {
    test('GET /quiz/confirm-submit renders Inertia page with counts', function () {
        $this->postJson('/quiz/start');

        // Answer only the first question — leave the second unanswered.
        $this->postJson('/quiz/answer', [
            'question_id' => $this->setup['singleQuestion']->id,
            'option_ids' => [$this->setup['singleCorrect']->id],
        ]);

        $this->get('/quiz/confirm-submit')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Candidate/Quiz/ConfirmSubmit')
                ->where('counts.total_questions', 2)
                ->where('counts.answered', 1)
                ->where('counts.unanswered', 1)
                ->where('quiz.id', $this->setup['quiz']->id)
            );
    });

    test('confirm-submit is blocked without a started attempt', function () {
        $this->get('/quiz/confirm-submit')->assertRedirect(route('candidate.pre-quiz'));
    });
});

describe('final-submit endpoint', function () {
    test('POST /quiz/final-submit scores and returns fullscreen-exit flag', function () {
        Event::fake([QuizAttemptSubmitted::class]);

        $this->postJson('/quiz/start');

        // Answer both questions correctly.
        $this->postJson('/quiz/answer', [
            'question_id' => $this->setup['singleQuestion']->id,
            'option_ids' => [$this->setup['singleCorrect']->id],
        ]);
        $this->postJson('/quiz/next-question');
        $this->postJson('/quiz/answer', [
            'question_id' => $this->setup['multiQuestion']->id,
            'option_ids' => [$this->setup['multiCorrect']->id],
        ]);

        $response = $this->postJson('/quiz/final-submit');

        $response->assertOk()
            ->assertJsonPath('submitted', true)
            ->assertJsonPath('exit_fullscreen', true)
            ->assertJsonPath('redirect', route('candidate.quiz.submitted'))
            ->assertJsonPath('attempt.status', 'submitted');

        $attempt = QuizAttempt::query()->latest('id')->firstOrFail();
        expect($attempt->status)->toBe(AttemptStatus::Submitted)
            ->and($attempt->submitted_at)->not->toBeNull();

        Event::assertDispatched(
            QuizAttemptSubmitted::class,
            fn (QuizAttemptSubmitted $event) => $event->attempt->id === $attempt->id,
        );
    });

    test('final-submit runs scoring on the attempt', function () {
        $this->postJson('/quiz/start');

        // Answer first question correctly (5 / 5) and leave the second blank (0 / 5).
        $this->postJson('/quiz/answer', [
            'question_id' => $this->setup['singleQuestion']->id,
            'option_ids' => [$this->setup['singleCorrect']->id],
        ]);

        $this->postJson('/quiz/final-submit')->assertOk();

        $attempt = QuizAttempt::query()->latest('id')->firstOrFail();
        expect((float) $attempt->auto_score)->toBe(50.0);
    });

    test('re-submitting a submitted attempt is a no-op', function () {
        Event::fake([QuizAttemptSubmitted::class]);

        $this->postJson('/quiz/start');
        $this->postJson('/quiz/final-submit')->assertOk();

        // Second call hits the middleware: attempt is no longer InProgress → 409.
        $response = $this->postJson('/quiz/final-submit');
        $response->assertStatus(409)
            ->assertJsonPath('redirect', route('candidate.quiz.submitted'));

        Event::assertDispatchedTimes(QuizAttemptSubmitted::class, 1);
    });
});

describe('post-submit lock', function () {
    test('GET /quiz/run on a submitted attempt redirects to /quiz/submitted', function () {
        $this->postJson('/quiz/start');
        $this->postJson('/quiz/final-submit')->assertOk();

        $this->get('/quiz/run')->assertRedirect('/quiz/submitted');
    });

    test('GET /quiz/current on a submitted attempt returns 409 JSON with redirect', function () {
        $this->postJson('/quiz/start');
        $this->postJson('/quiz/final-submit')->assertOk();

        $this->getJson('/quiz/current')
            ->assertStatus(409)
            ->assertJsonPath('redirect', route('candidate.quiz.submitted'));
    });

    test('POST /quiz/answer on a submitted attempt is rejected', function () {
        $this->postJson('/quiz/start');
        $this->postJson('/quiz/final-submit')->assertOk();

        $this->postJson('/quiz/answer', [
            'question_id' => $this->setup['singleQuestion']->id,
            'option_ids' => [$this->setup['singleCorrect']->id],
        ])->assertStatus(409);
    });

    test('GET /quiz/submitted renders Submitted Inertia page', function () {
        $this->postJson('/quiz/start');
        $this->postJson('/quiz/final-submit')->assertOk();

        $attempt = QuizAttempt::query()->latest('id')->firstOrFail();

        $this->get('/quiz/submitted')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Candidate/Quiz/Submitted')
                ->where('attempt.id', $attempt->id)
                ->where('attempt.status', 'submitted')
                ->where('quiz.id', $this->setup['quiz']->id)
            );
    });

    test('GET /quiz/submitted without session redirects to pre-quiz', function () {
        $this->actingAs($this->candidate, 'candidate')->withSession([]);

        $this->get('/quiz/submitted')->assertRedirect(route('candidate.pre-quiz'));
    });

    test('GET /quiz/submitted while attempt is still in progress redirects to run', function () {
        $this->postJson('/quiz/start');

        $this->get('/quiz/submitted')->assertRedirect(route('candidate.quiz.run'));
    });

    test('submitted page does NOT include score or answer data in props', function () {
        $this->postJson('/quiz/start');
        $this->postJson('/quiz/answer', [
            'question_id' => $this->setup['singleQuestion']->id,
            'option_ids' => [$this->setup['singleCorrect']->id],
        ]);
        $this->postJson('/quiz/final-submit')->assertOk();

        $this->get('/quiz/submitted')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Candidate/Quiz/Submitted')
                ->missing('attempt.auto_score')
                ->missing('attempt.final_score')
                ->missing('answers')
                ->missing('questions')
                ->missing('score')
            );
    });

    test('another candidate cannot view this candidate submitted page', function () {
        $this->postJson('/quiz/start');
        $this->postJson('/quiz/final-submit')->assertOk();

        $attempt = QuizAttempt::query()->latest('id')->firstOrFail();

        $intruder = Candidate::factory()->verified()->create();
        $this->actingAs($intruder, 'candidate')
            ->withSession(['quiz_attempt_id' => $attempt->id]);

        $this->get('/quiz/submitted')->assertRedirect(route('candidate.pre-quiz'));
    });
});
