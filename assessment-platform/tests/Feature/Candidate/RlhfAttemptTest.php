<?php

use App\Enums\RlhfTurnGenerationStatus;
use App\Enums\SelectedSide;
use App\Models\AttemptAnswer;
use App\Models\AttemptCameraSnapshot;
use App\Models\AttemptRlhfEvaluation;
use App\Models\AttemptRlhfFormResponse;
use App\Models\AttemptRlhfReview;
use App\Models\AttemptRlhfTurn;
use App\Models\AttemptSuspiciousEvent;
use App\Models\AuditLog;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\RlhfCriterion;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('complete RLHF attempt with 3 turns loads via eager loading efficiently', function () {
    // Build the question with 4 criteria
    $question = Question::factory()->rlhf()->create();
    $criteria = RlhfCriterion::factory()->count(4)->create(['question_id' => $question->id]);

    // Build the attempt and answer
    $attempt = QuizAttempt::factory()->create();
    $answer = AttemptAnswer::factory()->answered()->create([
        'quiz_attempt_id' => $attempt->id,
        'question_id' => $question->id,
    ]);

    // Build 3 turns, each with evaluations on both sides for all 4 criteria
    for ($turnNum = 1; $turnNum <= 3; $turnNum++) {
        $turn = AttemptRlhfTurn::factory()->completed()->create([
            'attempt_answer_id' => $answer->id,
            'turn_number' => $turnNum,
        ]);

        // 4 criteria × 2 sides = 8 evaluations per turn
        foreach ($criteria as $criterion) {
            AttemptRlhfEvaluation::factory()->create([
                'rlhf_turn_id' => $turn->id,
                'criterion_id' => $criterion->id,
                'response_side' => 'a',
            ]);
            AttemptRlhfEvaluation::factory()->create([
                'rlhf_turn_id' => $turn->id,
                'criterion_id' => $criterion->id,
                'response_side' => 'b',
            ]);
        }

        // 2 form responses per turn
        AttemptRlhfFormResponse::factory()->create([
            'rlhf_turn_id' => $turn->id,
            'stage' => 'post_prompt',
            'field_key' => 'quality_assessment',
        ]);
        AttemptRlhfFormResponse::factory()->create([
            'rlhf_turn_id' => $turn->id,
            'stage' => 'post_prompt',
            'field_key' => 'justification',
        ]);
    }

    // Add a review
    AttemptRlhfReview::factory()->create([
        'attempt_answer_id' => $answer->id,
    ]);

    // Eager load the entire graph and count queries
    $queryCount = 0;
    DB::listen(function () use (&$queryCount) {
        $queryCount++;
    });

    $loaded = AttemptAnswer::with([
        'rlhfTurns.evaluations.criterion',
        'rlhfTurns.formResponses',
        'rlhfReview',
    ])->find($answer->id);

    // Assert the query count is bounded (not N+1)
    // With eager loading: 1 (answer) + 1 (turns) + 1 (evaluations) + 1 (criteria) + 1 (formResponses) + 1 (review) = ~6
    expect($queryCount)->toBeLessThanOrEqual(7);

    // Assert full graph loaded
    expect($loaded->rlhfTurns)->toHaveCount(3);

    foreach ($loaded->rlhfTurns as $turn) {
        expect($turn->evaluations)->toHaveCount(8); // 4 criteria × 2 sides
        expect($turn->formResponses)->toHaveCount(2);
        expect($turn->generation_status)->toBe(RlhfTurnGenerationStatus::Ready);
        expect($turn->bothResponsesReady())->toBeTrue();
        expect($turn->selectedResponseText())->not->toBeNull();
    }

    // Turns are ordered by turn_number
    expect($loaded->rlhfTurns[0]->turn_number)->toBe(1);
    expect($loaded->rlhfTurns[1]->turn_number)->toBe(2);
    expect($loaded->rlhfTurns[2]->turn_number)->toBe(3);

    // Review loaded
    expect($loaded->rlhfReview)->not->toBeNull();

    // Total evaluations: 3 turns × 8 = 24
    $totalEvals = $loaded->rlhfTurns->sum(fn ($t) => $t->evaluations->count());
    expect($totalEvals)->toBe(24);
});

test('turn priorTurns returns only earlier turns', function () {
    $answer = AttemptAnswer::factory()->create();

    AttemptRlhfTurn::factory()->create(['attempt_answer_id' => $answer->id, 'turn_number' => 1]);
    AttemptRlhfTurn::factory()->create(['attempt_answer_id' => $answer->id, 'turn_number' => 2]);
    $turn3 = AttemptRlhfTurn::factory()->create(['attempt_answer_id' => $answer->id, 'turn_number' => 3]);

    $priorTurns = $turn3->priorTurns();

    expect($priorTurns)->toHaveCount(2)
        ->and($priorTurns[0]->turn_number)->toBe(1)
        ->and($priorTurns[1]->turn_number)->toBe(2);
});

test('turn bothResponsesReady returns false when incomplete', function () {
    $turn = AttemptRlhfTurn::factory()->create([
        'response_a' => 'Hello',
        'response_b' => null,
    ]);

    expect($turn->bothResponsesReady())->toBeFalse();
});

test('turn selectedResponseText returns correct side', function () {
    $turn = AttemptRlhfTurn::factory()->withResponses()->create([
        'selected_side' => SelectedSide::A,
    ]);

    expect($turn->selectedResponseText())->toBe($turn->response_a);

    $turnB = AttemptRlhfTurn::factory()->withResponses()->create([
        'selected_side' => SelectedSide::B,
    ]);

    expect($turnB->selectedResponseText())->toBe($turnB->response_b);
});

test('suspicious events and camera snapshots relate to attempt', function () {
    $attempt = QuizAttempt::factory()->create();

    AttemptSuspiciousEvent::factory()->count(3)->create([
        'quiz_attempt_id' => $attempt->id,
    ]);
    AttemptCameraSnapshot::factory()->count(2)->create([
        'quiz_attempt_id' => $attempt->id,
    ]);

    $attempt->refresh();

    expect($attempt->suspiciousEvents)->toHaveCount(3)
        ->and($attempt->cameraSnapshots)->toHaveCount(2);
});

test('audit log records morph relationship', function () {
    $user = User::factory()->create();
    $quiz = Quiz::factory()->create();

    $log = AuditLog::create([
        'user_id' => $user->id,
        'action' => 'quiz.published',
        'auditable_type' => $quiz->getMorphClass(),
        'auditable_id' => $quiz->id,
        'changes' => ['status' => ['draft', 'published']],
        'ip_address' => '127.0.0.1',
        'created_at' => now(),
    ]);

    expect($log->auditable)->toBeInstanceOf(Quiz::class)
        ->and($log->auditable->id)->toBe($quiz->id)
        ->and($log->changes)->toBeArray()
        ->and($log->user->id)->toBe($user->id);
});
