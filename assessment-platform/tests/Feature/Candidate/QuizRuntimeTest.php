<?php

use App\Actions\Attempts\AutoSubmitExpiredAttemptsAction;
use App\Enums\AttemptStatus;
use App\Enums\QuizNavigationMode;
use App\Models\Candidate;
use App\Models\CodingQuestionConfig;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizInvitation;
use App\Models\QuizSection;
use App\Models\QuizSectionQuestion;
use Illuminate\Support\Facades\Auth;

/**
 * @return array{
 *     quiz: Quiz,
 *     section1: QuizSection,
 *     section2: QuizSection,
 *     singleQuestion: Question,
 *     singleCorrect: QuestionOption,
 *     multiQuestion: Question,
 *     multiCorrect1: QuestionOption,
 *     multiCorrect2: QuestionOption,
 *     codingQuestion: Question,
 * }
 */
function makeRuntimeQuiz(): array
{
    $quiz = Quiz::factory()->published()->create([
        'navigation_mode' => QuizNavigationMode::Free,
        'time_limit_seconds' => 600,
    ]);

    $section1 = QuizSection::factory()->create([
        'quiz_id' => $quiz->id,
        'title' => 'Section 1',
        'position' => 0,
        'time_limit_seconds' => 300,
    ]);
    $section2 = QuizSection::factory()->create([
        'quiz_id' => $quiz->id,
        'title' => 'Section 2',
        'position' => 1,
    ]);

    $singleQuestion = Question::factory()->singleSelect()->create(['points' => 2]);
    $correct = QuestionOption::factory()->correct()->create([
        'question_id' => $singleQuestion->id,
        'position' => 0,
    ]);
    QuestionOption::factory()->create([
        'question_id' => $singleQuestion->id,
        'position' => 1,
    ]);

    $multiQuestion = Question::factory()->multiSelect()->create(['points' => 3]);
    $multiCorrect1 = QuestionOption::factory()->correct()->create([
        'question_id' => $multiQuestion->id,
        'position' => 0,
    ]);
    $multiCorrect2 = QuestionOption::factory()->correct()->create([
        'question_id' => $multiQuestion->id,
        'position' => 1,
    ]);
    QuestionOption::factory()->create([
        'question_id' => $multiQuestion->id,
        'position' => 2,
    ]);

    $codingQuestion = Question::factory()->coding()->create(['points' => 5]);
    CodingQuestionConfig::factory()->create([
        'question_id' => $codingQuestion->id,
    ]);

    QuizSectionQuestion::create([
        'quiz_section_id' => $section1->id,
        'question_id' => $singleQuestion->id,
        'question_version' => 1,
        'position' => 0,
    ]);
    QuizSectionQuestion::create([
        'quiz_section_id' => $section1->id,
        'question_id' => $multiQuestion->id,
        'question_version' => 1,
        'position' => 1,
    ]);
    QuizSectionQuestion::create([
        'quiz_section_id' => $section2->id,
        'question_id' => $codingQuestion->id,
        'question_version' => 1,
        'position' => 0,
    ]);

    return [
        'quiz' => $quiz,
        'section1' => $section1,
        'section2' => $section2,
        'singleQuestion' => $singleQuestion,
        'singleCorrect' => $correct,
        'multiQuestion' => $multiQuestion,
        'multiCorrect1' => $multiCorrect1,
        'multiCorrect2' => $multiCorrect2,
        'codingQuestion' => $codingQuestion,
    ];
}

beforeEach(function () {
    $this->setup = makeRuntimeQuiz();
    $this->candidate = Candidate::factory()->verified()->create();
    $this->invitation = QuizInvitation::factory()->create(['quiz_id' => $this->setup['quiz']->id]);

    $this->actingAs($this->candidate, 'candidate')
        ->withSession(['quiz_invitation_token' => $this->invitation->token]);
});

describe('starting a runtime attempt', function () {
    test('POST /quiz/start creates attempt and snapshots answers', function () {
        $response = $this->postJson('/quiz/start');

        $response->assertOk()
            ->assertJsonPath('state.quiz.id', $this->setup['quiz']->id)
            ->assertJsonPath('state.section.id', $this->setup['section1']->id)
            ->assertJsonPath('state.question.id', $this->setup['singleQuestion']->id)
            ->assertJsonPath('state.progress.total_questions', 3);

        $attempt = QuizAttempt::query()->latest('id')->firstOrFail();
        expect($attempt->status)->toBe(AttemptStatus::InProgress)
            ->and($attempt->current_section_id)->toBe($this->setup['section1']->id)
            ->and($attempt->current_question_id)->toBe($this->setup['singleQuestion']->id)
            ->and($attempt->answers)->toHaveCount(3);
    });

    test('restarting returns the existing in-progress attempt', function () {
        $this->postJson('/quiz/start')->assertOk();
        $first = QuizAttempt::query()->latest('id')->firstOrFail();

        $this->postJson('/quiz/start')->assertOk();
        expect(QuizAttempt::count())->toBe(1)
            ->and(QuizAttempt::query()->latest('id')->first()->id)->toBe($first->id);
    });
});

describe('recording answers', function () {
    test('records single-select and advances through the quiz', function () {
        $this->postJson('/quiz/start');

        $this->postJson('/quiz/answer', [
            'question_id' => $this->setup['singleQuestion']->id,
            'option_ids' => [$this->setup['singleCorrect']->id],
        ])->assertOk();

        $attempt = QuizAttempt::query()->latest('id')->firstOrFail();
        $answer = $attempt->answers()->where('question_id', $this->setup['singleQuestion']->id)->first();
        expect($answer->selections)->toHaveCount(1)
            ->and((int) $answer->selections->first()->question_option_id)->toBe($this->setup['singleCorrect']->id);

        $this->postJson('/quiz/next-question')
            ->assertOk()
            ->assertJsonPath('question.id', $this->setup['multiQuestion']->id);
    });

    test('records multi-select selections', function () {
        $this->postJson('/quiz/start');
        $this->postJson('/quiz/next-question');

        $this->postJson('/quiz/answer', [
            'question_id' => $this->setup['multiQuestion']->id,
            'option_ids' => [$this->setup['multiCorrect1']->id, $this->setup['multiCorrect2']->id],
        ])->assertOk();

        $attempt = QuizAttempt::query()->latest('id')->firstOrFail();
        $answer = $attempt->answers()->where('question_id', $this->setup['multiQuestion']->id)->first();
        expect($answer->selections)->toHaveCount(2);
    });

    test('records coding submission', function () {
        $this->postJson('/quiz/start');
        $this->postJson('/quiz/next-section');

        $this->postJson('/quiz/answer', [
            'question_id' => $this->setup['codingQuestion']->id,
            'language' => 'python',
            'code' => 'print(42)',
        ])->assertOk();

        $attempt = QuizAttempt::query()->latest('id')->firstOrFail();
        $answer = $attempt->answers()->where('question_id', $this->setup['codingQuestion']->id)->first();
        expect($answer->codingSubmission)->not->toBeNull()
            ->and($answer->codingSubmission->language)->toBe('python')
            ->and($answer->codingSubmission->code)->toBe('print(42)');
    });
});

describe('navigation', function () {
    test('next-section jumps to the next section first question', function () {
        $this->postJson('/quiz/start');

        $this->postJson('/quiz/next-section')
            ->assertOk()
            ->assertJsonPath('section.id', $this->setup['section2']->id)
            ->assertJsonPath('question.id', $this->setup['codingQuestion']->id);
    });

    test('forward-only blocks previous-question', function () {
        $this->setup['quiz']->update(['navigation_mode' => QuizNavigationMode::ForwardOnly]);
        $this->postJson('/quiz/start');
        $this->postJson('/quiz/next-question');

        $this->postJson('/quiz/previous-question')->assertStatus(403);
    });

    test('free navigation allows going backward', function () {
        $this->postJson('/quiz/start');
        $this->postJson('/quiz/next-question');

        $this->postJson('/quiz/previous-question')
            ->assertOk()
            ->assertJsonPath('question.id', $this->setup['singleQuestion']->id);
    });
});

describe('submitting', function () {
    test('submit scores correctly', function () {
        $this->postJson('/quiz/start');

        // Single-select correctly (2 / 2)
        $this->postJson('/quiz/answer', [
            'question_id' => $this->setup['singleQuestion']->id,
            'option_ids' => [$this->setup['singleCorrect']->id],
        ]);

        // Multi-select correctly (3 / 3)
        $this->postJson('/quiz/next-question');
        $this->postJson('/quiz/answer', [
            'question_id' => $this->setup['multiQuestion']->id,
            'option_ids' => [$this->setup['multiCorrect1']->id, $this->setup['multiCorrect2']->id],
        ]);

        // Skip coding (0 / 5)
        $response = $this->postJson('/quiz/final-submit');

        $response->assertOk()->assertJsonPath('submitted', true);

        $attempt = QuizAttempt::query()->latest('id')->firstOrFail();
        expect($attempt->status)->toBe(AttemptStatus::Submitted)
            ->and($attempt->submitted_at)->not->toBeNull()
            ->and((float) $attempt->auto_score)->toBe(50.0);
    });
});

describe('attempt ownership', function () {
    test('middleware rejects access without a started attempt', function () {
        $this->getJson('/quiz/current')->assertStatus(404);
    });

    test('stranger candidate cannot hijack an attempt via the same session', function () {
        $this->postJson('/quiz/start');

        $intruder = Candidate::factory()->verified()->create();
        Auth::guard('candidate')->login($intruder);

        $this->getJson('/quiz/current')->assertStatus(403);
    });
});

describe('auto-submit', function () {
    test('AutoSubmitExpiredAttemptsAction closes stale attempts', function () {
        $this->postJson('/quiz/start');
        $attempt = QuizAttempt::query()->latest('id')->firstOrFail();

        $attempt->update(['started_at' => now()->subSeconds(10_000)]);

        app(AutoSubmitExpiredAttemptsAction::class)->handle();

        expect($attempt->fresh()->status)->toBe(AttemptStatus::AutoSubmitted);
    });
});
