<?php

use App\Enums\AnswerStatus;
use App\Enums\RlhfReviewStatus;
use App\Models\AttemptAnswer;
use App\Models\AttemptRlhfReview;
use App\Models\AttemptRlhfTurn;
use App\Models\AuditLog;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizSection;
use App\Models\QuizSectionQuestion;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

function makeRlhfReviewer(array $permissions): User
{
    $user = User::factory()->create();
    $role = Role::findOrCreate('RlhfReviewer '.uniqid(), 'web');
    $role->syncPermissions($permissions);
    $user->assignRole($role);

    return $user;
}

/**
 * @return array{attempt: QuizAttempt, rlhfAnswer: AttemptAnswer, mcqAnswer: AttemptAnswer|null}
 */
function makeRlhfAttempt(bool $withMcq = false): array
{
    $quiz = Quiz::factory()->published()->create();
    $section = QuizSection::factory()->create(['quiz_id' => $quiz->id, 'position' => 0]);

    $rlhfQuestion = Question::factory()->rlhf()->create(['points' => 10]);
    QuizSectionQuestion::create([
        'quiz_section_id' => $section->id,
        'question_id' => $rlhfQuestion->id,
        'question_version' => 1,
        'position' => 0,
    ]);

    $mcqQuestion = null;
    if ($withMcq) {
        $mcqQuestion = Question::factory()->singleSelect()->create(['points' => 10]);
        QuizSectionQuestion::create([
            'quiz_section_id' => $section->id,
            'question_id' => $mcqQuestion->id,
            'question_version' => 1,
            'position' => 1,
        ]);
    }

    $attempt = QuizAttempt::factory()->submitted()->create([
        'quiz_id' => $quiz->id,
        'rlhf_review_status' => RlhfReviewStatus::Pending,
        'auto_score' => 100,  // MCQ auto = full
        'final_score' => null,
    ]);

    $rlhfAnswer = AttemptAnswer::factory()->create([
        'quiz_attempt_id' => $attempt->id,
        'question_id' => $rlhfQuestion->id,
        'question_version' => 1,
        'status' => AnswerStatus::Answered,
    ]);

    AttemptRlhfTurn::factory()->create([
        'attempt_answer_id' => $rlhfAnswer->id,
        'turn_number' => 1,
        'response_a' => 'A text',
        'response_b' => 'B text',
    ]);

    $mcqAnswer = null;
    if ($mcqQuestion !== null) {
        $mcqAnswer = AttemptAnswer::factory()->create([
            'quiz_attempt_id' => $attempt->id,
            'question_id' => $mcqQuestion->id,
            'question_version' => 1,
            'status' => AnswerStatus::Answered,
            'auto_score' => 10,
        ]);
    }

    return [
        'attempt' => $attempt,
        'rlhfAnswer' => $rlhfAnswer,
        'mcqAnswer' => $mcqAnswer,
    ];
}

describe('rlhf review show', function () {
    test('shows turns, criteria, and review permissions', function () {
        $user = makeRlhfReviewer(['rlhf.view', 'rlhf.score', 'rlhf.finalize']);
        $setup = makeRlhfAttempt();

        $this->actingAs($user)
            ->get("/admin/rlhf/review/{$setup['rlhfAnswer']->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Results/RlhfReview')
                ->has('turns', 1)
                ->where('turns.0.turn_number', 1)
                ->where('permissions.can_score', true)
                ->where('permissions.can_finalize', true)
                ->where('review', null)
            );
    });

    test('shows existing review when present', function () {
        $user = makeRlhfReviewer(['rlhf.view']);
        $setup = makeRlhfAttempt();

        AttemptRlhfReview::factory()->create([
            'attempt_answer_id' => $setup['rlhfAnswer']->id,
            'reviewer_id' => $user->id,
            'score' => 82.5,
            'decision' => 'partially_accepted',
            'comments' => 'Good effort.',
            'finalized' => false,
        ]);

        $this->actingAs($user)
            ->get("/admin/rlhf/review/{$setup['rlhfAnswer']->id}")
            ->assertInertia(fn ($page) => $page
                ->where('review.score', 82.5)
                ->where('review.decision', 'partially_accepted')
                ->where('review.comments', 'Good effort.')
                ->where('review.finalized', false)
            );
    });

    test('blocks users without rlhf.view', function () {
        $user = User::factory()->create();
        $setup = makeRlhfAttempt();

        $this->actingAs($user)
            ->get("/admin/rlhf/review/{$setup['rlhfAnswer']->id}")
            ->assertForbidden();
    });

    test('returns 404 for non-rlhf answer', function () {
        $user = makeRlhfReviewer(['rlhf.view']);
        $mcqAnswer = AttemptAnswer::factory()->create();

        $this->actingAs($user)
            ->get("/admin/rlhf/review/{$mcqAnswer->id}")
            ->assertNotFound();
    });
});

describe('rlhf review store', function () {
    test('saves draft review and updates answer reviewer_score', function () {
        $user = makeRlhfReviewer(['rlhf.view', 'rlhf.score']);
        $setup = makeRlhfAttempt();

        $this->actingAs($user)
            ->postJson("/admin/rlhf/review/{$setup['rlhfAnswer']->id}", [
                'score' => 75.5,
                'decision' => 'accepted',
                'comments' => 'Solid reasoning.',
            ])
            ->assertOk()
            ->assertJsonPath('saved', true)
            ->assertJsonPath('review.score', 75.5)
            ->assertJsonPath('review.decision', 'accepted')
            ->assertJsonPath('review.finalized', false);

        $review = AttemptRlhfReview::query()
            ->where('attempt_answer_id', $setup['rlhfAnswer']->id)
            ->firstOrFail();
        expect((float) $review->score)->toBe(75.5)
            ->and($review->reviewer_id)->toBe($user->id);

        expect((float) $setup['rlhfAnswer']->fresh()->reviewer_score)->toBe(75.5);
    });

    test('blocks store without rlhf.score', function () {
        $user = makeRlhfReviewer(['rlhf.view']);
        $setup = makeRlhfAttempt();

        $this->actingAs($user)
            ->postJson("/admin/rlhf/review/{$setup['rlhfAnswer']->id}", [
                'score' => 90,
                'decision' => 'accepted',
            ])
            ->assertForbidden();
    });

    test('store writes to audit log', function () {
        $user = makeRlhfReviewer(['rlhf.view', 'rlhf.score']);
        $setup = makeRlhfAttempt();

        $this->actingAs($user)->postJson("/admin/rlhf/review/{$setup['rlhfAnswer']->id}", [
            'score' => 60,
            'decision' => 'rejected',
        ]);

        $log = AuditLog::query()->where('action', 'rlhf.review_saved')->latest('id')->first();
        expect($log)->not->toBeNull();
    });

    test('validates decision is a known value', function () {
        $user = makeRlhfReviewer(['rlhf.view', 'rlhf.score']);
        $setup = makeRlhfAttempt();

        $this->actingAs($user)
            ->postJson("/admin/rlhf/review/{$setup['rlhfAnswer']->id}", [
                'score' => 90,
                'decision' => 'maybe',
            ])
            ->assertStatus(422);
    });
});

describe('rlhf review finalize', function () {
    test('cannot finalize without rlhf.finalize permission', function () {
        $user = makeRlhfReviewer(['rlhf.view', 'rlhf.score']);
        $setup = makeRlhfAttempt();

        AttemptRlhfReview::factory()->create([
            'attempt_answer_id' => $setup['rlhfAnswer']->id,
            'reviewer_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->postJson("/admin/rlhf/review/{$setup['rlhfAnswer']->id}/finalize")
            ->assertForbidden();
    });

    test('finalizing the last RLHF review recalculates attempt final_score', function () {
        $user = makeRlhfReviewer(['rlhf.view', 'rlhf.score', 'rlhf.finalize']);
        $setup = makeRlhfAttempt(withMcq: true);

        // Save a draft review first.
        $this->actingAs($user)->postJson("/admin/rlhf/review/{$setup['rlhfAnswer']->id}", [
            'score' => 80,
            'decision' => 'accepted',
        ])->assertOk();

        // Finalize.
        $response = $this->actingAs($user)
            ->postJson("/admin/rlhf/review/{$setup['rlhfAnswer']->id}/finalize");

        $response->assertOk()
            ->assertJsonPath('finalized', true)
            ->assertJsonPath('review.finalized', true)
            ->assertJsonPath('attempt.rlhf_review_status', 'completed');

        $attempt = $setup['attempt']->fresh();
        expect($attempt->rlhf_review_status)->toBe(RlhfReviewStatus::Completed);

        // MCQ got 10 points auto_score, RLHF got 80% of 10 = 8 points.
        // final_score = (10 + 8) / 20 * 100 = 90.0
        expect((float) $attempt->final_score)->toBe(90.0);
    });

    test('finalize a single RLHF answer does not flip attempt when others pending', function () {
        $user = makeRlhfReviewer(['rlhf.view', 'rlhf.score', 'rlhf.finalize']);
        $setup = makeRlhfAttempt();

        // Add a second unfinalized RLHF answer on the same attempt.
        $q2 = Question::factory()->rlhf()->create(['points' => 10]);
        $section = $setup['attempt']->quiz->sections()->first();
        QuizSectionQuestion::create([
            'quiz_section_id' => $section->id,
            'question_id' => $q2->id,
            'question_version' => 1,
            'position' => 1,
        ]);
        AttemptAnswer::factory()->create([
            'quiz_attempt_id' => $setup['attempt']->id,
            'question_id' => $q2->id,
            'question_version' => 1,
            'status' => AnswerStatus::Answered,
        ]);

        // Save + finalize ONLY the first RLHF answer.
        $this->actingAs($user)->postJson("/admin/rlhf/review/{$setup['rlhfAnswer']->id}", [
            'score' => 80,
            'decision' => 'accepted',
        ])->assertOk();

        $this->actingAs($user)
            ->postJson("/admin/rlhf/review/{$setup['rlhfAnswer']->id}/finalize")
            ->assertOk()
            ->assertJsonPath('attempt.rlhf_review_status', 'pending')
            ->assertJsonPath('attempt.final_score', null);
    });

    test('finalize throws when no prior review exists', function () {
        $user = makeRlhfReviewer(['rlhf.view', 'rlhf.score', 'rlhf.finalize']);
        $setup = makeRlhfAttempt();

        // Skip the save step.
        $response = $this->actingAs($user)
            ->postJson("/admin/rlhf/review/{$setup['rlhfAnswer']->id}/finalize");

        // RuntimeException surfaces as 500 in JSON responses.
        $response->assertStatus(500);
    });
});
