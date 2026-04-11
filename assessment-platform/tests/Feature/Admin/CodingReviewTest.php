<?php

use App\Actions\Attempts\RecordCodingAnswerAction;
use App\Contracts\CodeRunners\CodeRunner;
use App\Contracts\CodeRunners\TestCaseResult;
use App\Contracts\CodeRunners\TestRunResult;
use App\Enums\AnswerStatus;
use App\Jobs\Coding\RunCodingSubmissionJob;
use App\Models\AttemptAnswer;
use App\Models\AttemptCodingSubmission;
use App\Models\AttemptCodingTestResult;
use App\Models\AuditLog;
use App\Models\CodingQuestionConfig;
use App\Models\CodingTestCase;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizSection;
use App\Models\QuizSectionQuestion;
use App\Models\User;
use App\Services\Scoring\QuizScoringService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

function makeCodingReviewer(array $permissions): User
{
    $user = User::factory()->create();
    $role = Role::findOrCreate('CodingReviewer '.uniqid(), 'web');
    $role->syncPermissions($permissions);
    $user->assignRole($role);

    return $user;
}

/**
 * @return array{
 *     attempt: QuizAttempt,
 *     answer: AttemptAnswer,
 *     question: Question,
 *     testCases: array<int, CodingTestCase>,
 * }
 */
function makeCodingSetup(int $testCaseCount = 3): array
{
    $quiz = Quiz::factory()->published()->create();
    $section = QuizSection::factory()->create(['quiz_id' => $quiz->id, 'position' => 0]);

    $question = Question::factory()->coding()->create(['points' => 10]);
    CodingQuestionConfig::factory()->create(['question_id' => $question->id]);

    $testCases = [];
    for ($i = 0; $i < $testCaseCount; $i++) {
        $testCases[] = CodingTestCase::factory()->create([
            'question_id' => $question->id,
            'input' => "input-{$i}",
            'expected_output' => "MATCH_ME_{$i}",
            'weight' => 1.0,
        ]);
    }

    QuizSectionQuestion::create([
        'quiz_section_id' => $section->id,
        'question_id' => $question->id,
        'question_version' => 1,
        'position' => 0,
    ]);

    $attempt = QuizAttempt::factory()->submitted()->create(['quiz_id' => $quiz->id]);

    $answer = AttemptAnswer::factory()->create([
        'quiz_attempt_id' => $attempt->id,
        'question_id' => $question->id,
        'question_version' => 1,
        'status' => AnswerStatus::Answered,
    ]);

    return compact('attempt', 'answer', 'question', 'testCases');
}

describe('RunCodingSubmissionJob', function () {
    test('runs code via the runner and persists test results', function () {
        $setup = makeCodingSetup(testCaseCount: 3);

        // Candidate's code contains only the first expected token.
        $submission = AttemptCodingSubmission::factory()->create([
            'attempt_answer_id' => $setup['answer']->id,
            'language' => 'python',
            'code' => 'print("MATCH_ME_0")',
        ]);

        // Use the real LocalStubCodeRunner bound in AppServiceProvider.
        (new RunCodingSubmissionJob($submission->id))->handle(
            app(CodeRunner::class),
            app(QuizScoringService::class),
        );

        expect($submission->testResults()->count())->toBe(3)
            ->and($submission->testResults()->where('passed', true)->count())->toBe(1)
            ->and($submission->testResults()->where('passed', false)->count())->toBe(2);
    });

    test('recalculates attempt auto_score using weighted pass rate', function () {
        $setup = makeCodingSetup(testCaseCount: 4);

        // Matches 2 of 4 expected tokens.
        AttemptCodingSubmission::factory()->create([
            'attempt_answer_id' => $setup['answer']->id,
            'language' => 'python',
            'code' => 'print("MATCH_ME_0 MATCH_ME_1")',
        ]);

        $submission = $setup['answer']->fresh()->codingSubmission;

        (new RunCodingSubmissionJob($submission->id))->handle(
            app(CodeRunner::class),
            app(QuizScoringService::class),
        );

        $answer = $setup['answer']->fresh();
        // Scoring: 2/4 pass rate * 10 points = 5.0 points
        expect((float) $answer->auto_score)->toBe(5.0);
    });

    test('uses a mocked runner in isolation', function () {
        $setup = makeCodingSetup(testCaseCount: 2);

        $submission = AttemptCodingSubmission::factory()->create([
            'attempt_answer_id' => $setup['answer']->id,
            'language' => 'python',
            'code' => 'irrelevant',
        ]);

        $mock = new class implements CodeRunner
        {
            public function run(string $code, string $language, array $testCases): TestRunResult
            {
                return new TestRunResult(
                    compiled: true,
                    passed: 2,
                    failed: 0,
                    total: 2,
                    results: [
                        new TestCaseResult(name: 'Case 0', passed: true, output: 'x', executionTimeMs: 5.5),
                        new TestCaseResult(name: 'Case 1', passed: true, output: 'y', executionTimeMs: 3.3),
                    ],
                );
            }
        };

        (new RunCodingSubmissionJob($submission->id))->handle(
            $mock,
            app(QuizScoringService::class),
        );

        expect(AttemptCodingTestResult::query()->where('coding_submission_id', $submission->id)->count())->toBe(2)
            ->and(AttemptCodingTestResult::query()->where('passed', true)->count())->toBe(2);

        // All tests passed → full marks.
        expect((float) $setup['answer']->fresh()->auto_score)->toBe(10.0);
    });
});

describe('RecordCodingAnswerAction dispatch', function () {
    test('dispatches RunCodingSubmissionJob when a candidate submits code', function () {
        Queue::fake();

        $setup = makeCodingSetup();
        $action = app(RecordCodingAnswerAction::class);

        $action->handle($setup['answer'], 'python', 'print(42)');

        Queue::assertPushed(RunCodingSubmissionJob::class);
    });
});

describe('CodingReviewController show', function () {
    test('blocks users without coding.view', function () {
        $user = User::factory()->create();
        $setup = makeCodingSetup();

        $this->actingAs($user)
            ->get("/admin/coding/review/{$setup['answer']->id}")
            ->assertForbidden();
    });

    test('returns submission + test results for users with coding.view', function () {
        $user = makeCodingReviewer(['coding.view']);
        $setup = makeCodingSetup();

        AttemptCodingSubmission::factory()->create([
            'attempt_answer_id' => $setup['answer']->id,
            'language' => 'python',
            'code' => 'print("MATCH_ME_0")',
        ]);

        (new RunCodingSubmissionJob($setup['answer']->fresh()->codingSubmission->id))->handle(
            app(CodeRunner::class),
            app(QuizScoringService::class),
        );

        $this->actingAs($user)
            ->get("/admin/coding/review/{$setup['answer']->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Results/CodingReview')
                ->where('submission.language', 'python')
                ->has('test_results', 3)
                ->where('permissions.can_rerun', false)
                ->where('permissions.can_override', false)
            );
    });

    test('returns 404 when answer is not a coding answer', function () {
        $user = makeCodingReviewer(['coding.view']);
        $mcqQuestion = Question::factory()->singleSelect()->create();
        $nonCoding = AttemptAnswer::factory()->create([
            'question_id' => $mcqQuestion->id,
        ]);

        $this->actingAs($user)
            ->get("/admin/coding/review/{$nonCoding->id}")
            ->assertNotFound();
    });
});

describe('CodingReviewController rerun', function () {
    test('rerun requires coding.rerun permission', function () {
        $user = makeCodingReviewer(['coding.view']);
        $setup = makeCodingSetup();

        AttemptCodingSubmission::factory()->create([
            'attempt_answer_id' => $setup['answer']->id,
        ]);

        $this->actingAs($user)
            ->postJson("/admin/coding/review/{$setup['answer']->id}/rerun")
            ->assertForbidden();
    });

    test('rerun dispatches the job and writes an audit entry', function () {
        Queue::fake();
        $user = makeCodingReviewer(['coding.view', 'coding.rerun']);
        $setup = makeCodingSetup();

        AttemptCodingSubmission::factory()->create([
            'attempt_answer_id' => $setup['answer']->id,
        ]);

        $this->actingAs($user)
            ->postJson("/admin/coding/review/{$setup['answer']->id}/rerun")
            ->assertOk()
            ->assertJsonPath('dispatched', true);

        Queue::assertPushed(RunCodingSubmissionJob::class);

        $log = AuditLog::query()->where('action', 'coding.rerun')->latest('id')->first();
        expect($log)->not->toBeNull();
    });

    test('rerun 404s when no submission exists', function () {
        $user = makeCodingReviewer(['coding.view', 'coding.rerun']);
        $setup = makeCodingSetup();

        $this->actingAs($user)
            ->postJson("/admin/coding/review/{$setup['answer']->id}/rerun")
            ->assertNotFound();
    });
});

describe('CodingReviewController override', function () {
    test('override requires coding.override permission', function () {
        $user = makeCodingReviewer(['coding.view']);
        $setup = makeCodingSetup();

        $this->actingAs($user)
            ->postJson("/admin/coding/review/{$setup['answer']->id}/override", [
                'reviewer_score' => 8,
                'reason' => 'Hidden edge case false fail',
            ])
            ->assertForbidden();
    });

    test('override writes reviewer_score and audit log', function () {
        $user = makeCodingReviewer(['coding.view', 'coding.override']);
        $setup = makeCodingSetup();

        $this->actingAs($user)
            ->postJson("/admin/coding/review/{$setup['answer']->id}/override", [
                'reviewer_score' => 9.5,
                'reason' => 'Partial credit for near-miss output.',
            ])
            ->assertOk()
            ->assertJsonPath('reviewer_score', 9.5);

        expect((float) $setup['answer']->fresh()->reviewer_score)->toBe(9.5);

        $log = AuditLog::query()->where('action', 'coding.override')->latest('id')->first();
        expect($log)->not->toBeNull()
            ->and($log->changes['new_reviewer_score'])->toBe(9.5)
            ->and($log->changes['reason'])->toBe('Partial credit for near-miss output.');
    });

    test('override requires a non-empty reason', function () {
        $user = makeCodingReviewer(['coding.view', 'coding.override']);
        $setup = makeCodingSetup();

        $this->actingAs($user)
            ->postJson("/admin/coding/review/{$setup['answer']->id}/override", [
                'reviewer_score' => 8,
                'reason' => '',
            ])
            ->assertStatus(422);
    });
});
