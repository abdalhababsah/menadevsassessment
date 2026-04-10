<?php

use App\Enums\AnswerStatus;
use App\Enums\AttemptStatus;
use App\Enums\RlhfReviewStatus;
use App\Models\AttemptAnswer;
use App\Models\AttemptAnswerSelection;
use App\Models\AttemptCodingSubmission;
use App\Models\AttemptCodingTestResult;
use App\Models\Candidate;
use App\Models\CodingTestCase;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Database\UniqueConstraintViolationException;

describe('starting an attempt', function () {
    test('creates an in-progress attempt for a candidate', function () {
        $quiz = Quiz::factory()->published()->create();
        $candidate = Candidate::factory()->verified()->create();

        $attempt = QuizAttempt::factory()->create([
            'quiz_id' => $quiz->id,
            'candidate_id' => $candidate->id,
        ]);

        expect($attempt->status)->toBe(AttemptStatus::InProgress)
            ->and($attempt->isInProgress())->toBeTrue()
            ->and($attempt->isComplete())->toBeFalse()
            ->and($attempt->started_at)->not->toBeNull()
            ->and($attempt->submitted_at)->toBeNull()
            ->and($attempt->rlhf_review_status)->toBe(RlhfReviewStatus::NotRequired);
    });

    test('attempt belongs to quiz and candidate', function () {
        $attempt = QuizAttempt::factory()->create();

        expect($attempt->quiz)->toBeInstanceOf(Quiz::class)
            ->and($attempt->candidate)->toBeInstanceOf(Candidate::class);
    });

    test('submitted attempt is complete', function () {
        $attempt = QuizAttempt::factory()->submitted()->create();

        expect($attempt->isComplete())->toBeTrue()
            ->and($attempt->isInProgress())->toBeFalse()
            ->and($attempt->submitted_at)->not->toBeNull();
    });

    test('auto-submitted attempt is complete', function () {
        $attempt = QuizAttempt::factory()->autoSubmitted()->create();

        expect($attempt->status)->toBe(AttemptStatus::AutoSubmitted)
            ->and($attempt->isComplete())->toBeTrue();
    });
});

describe('recording MCQ selections', function () {
    test('records option selections for an answer', function () {
        $question = Question::factory()->singleSelect()->create();
        $correctOption = QuestionOption::factory()->correct()->create(['question_id' => $question->id]);
        $wrongOption = QuestionOption::factory()->create(['question_id' => $question->id]);

        $attempt = QuizAttempt::factory()->create();
        $answer = AttemptAnswer::factory()->answered()->create([
            'quiz_attempt_id' => $attempt->id,
            'question_id' => $question->id,
        ]);

        AttemptAnswerSelection::create([
            'attempt_answer_id' => $answer->id,
            'question_option_id' => $correctOption->id,
        ]);

        expect($answer->selections)->toHaveCount(1)
            ->and($answer->selections->first()->option->is_correct)->toBeTrue();
    });

    test('multi-select records multiple selections', function () {
        $question = Question::factory()->multiSelect()->create();
        $option1 = QuestionOption::factory()->correct()->create(['question_id' => $question->id, 'position' => 0]);
        $option2 = QuestionOption::factory()->correct()->create(['question_id' => $question->id, 'position' => 1]);
        $option3 = QuestionOption::factory()->create(['question_id' => $question->id, 'position' => 2]);

        $attempt = QuizAttempt::factory()->create();
        $answer = AttemptAnswer::factory()->answered()->create([
            'quiz_attempt_id' => $attempt->id,
            'question_id' => $question->id,
        ]);

        AttemptAnswerSelection::create(['attempt_answer_id' => $answer->id, 'question_option_id' => $option1->id]);
        AttemptAnswerSelection::create(['attempt_answer_id' => $answer->id, 'question_option_id' => $option2->id]);

        expect($answer->selections)->toHaveCount(2);
    });

    test('duplicate selection is rejected by unique constraint', function () {
        $answer = AttemptAnswer::factory()->create();
        $option = QuestionOption::factory()->create();

        AttemptAnswerSelection::create([
            'attempt_answer_id' => $answer->id,
            'question_option_id' => $option->id,
        ]);

        expect(fn () => AttemptAnswerSelection::create([
            'attempt_answer_id' => $answer->id,
            'question_option_id' => $option->id,
        ]))->toThrow(UniqueConstraintViolationException::class);
    });
});

describe('coding submission', function () {
    test('submits code for a coding question', function () {
        $question = Question::factory()->coding()->create();
        $attempt = QuizAttempt::factory()->create();

        $answer = AttemptAnswer::factory()->answered()->create([
            'quiz_attempt_id' => $attempt->id,
            'question_id' => $question->id,
        ]);

        $submission = AttemptCodingSubmission::factory()->create([
            'attempt_answer_id' => $answer->id,
            'language' => 'python',
            'code' => 'def solve(n): return n * 2',
        ]);

        expect($answer->codingSubmission)->toBeInstanceOf(AttemptCodingSubmission::class)
            ->and($submission->language)->toBe('python')
            ->and($submission->submitted_at)->not->toBeNull();
    });

    test('coding submission has test results', function () {
        $question = Question::factory()->coding()->create();
        $testCase1 = CodingTestCase::factory()->create(['question_id' => $question->id]);
        $testCase2 = CodingTestCase::factory()->create(['question_id' => $question->id]);

        $answer = AttemptAnswer::factory()->create(['question_id' => $question->id]);
        $submission = AttemptCodingSubmission::factory()->create([
            'attempt_answer_id' => $answer->id,
        ]);

        AttemptCodingTestResult::factory()->passed()->create([
            'coding_submission_id' => $submission->id,
            'test_case_id' => $testCase1->id,
        ]);
        AttemptCodingTestResult::factory()->failed('Wrong answer')->create([
            'coding_submission_id' => $submission->id,
            'test_case_id' => $testCase2->id,
        ]);

        $submission->refresh();

        expect($submission->testResults)->toHaveCount(2);

        $passed = $submission->testResults->where('passed', true);
        $failed = $submission->testResults->where('passed', false);

        expect($passed)->toHaveCount(1)
            ->and($failed)->toHaveCount(1)
            ->and($failed->first()->error)->toBe('Wrong answer');
    });
});

describe('answers ordering and status', function () {
    test('answers are fetched in creation order', function () {
        $attempt = QuizAttempt::factory()->create();

        $answer1 = AttemptAnswer::factory()->create(['quiz_attempt_id' => $attempt->id]);
        $answer2 = AttemptAnswer::factory()->create(['quiz_attempt_id' => $attempt->id]);
        $answer3 = AttemptAnswer::factory()->create(['quiz_attempt_id' => $attempt->id]);

        $answers = $attempt->answers;

        expect($answers)->toHaveCount(3)
            ->and($answers[0]->id)->toBe($answer1->id)
            ->and($answers[1]->id)->toBe($answer2->id)
            ->and($answers[2]->id)->toBe($answer3->id);
    });

    test('answer status tracks unanswered to answered transition', function () {
        $answer = AttemptAnswer::factory()->create();

        expect($answer->status)->toBe(AnswerStatus::Unanswered);

        $answer->update([
            'status' => AnswerStatus::Answered,
            'answered_at' => now(),
            'time_spent_seconds' => 45,
        ]);

        expect($answer->fresh()->status)->toBe(AnswerStatus::Answered)
            ->and($answer->fresh()->time_spent_seconds)->toBe(45);
    });
});
