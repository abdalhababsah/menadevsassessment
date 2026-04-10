<?php

use App\Actions\Attempts\StartQuizAttemptAction;
use App\Enums\AttemptStatus;
use App\Models\Candidate;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizInvitation;
use App\Models\QuizSection;
use App\Models\QuizSectionQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createQuizWithQuestion(string $type = 'single_select'): array
{
    $quiz = Quiz::factory()->published()->create(['time_limit_seconds' => 1200]);
    $section = QuizSection::factory()->create([
        'quiz_id' => $quiz->id,
        'position' => 0,
    ]);

    $questionFactory = Question::factory()->state(['version' => 1]);
    $question = match ($type) {
        'multi_select' => $questionFactory->multiSelect()->create(),
        'coding' => $questionFactory->coding()->create(),
        default => $questionFactory->singleSelect()->create(),
    };

    if ($type !== 'coding') {
        QuestionOption::factory()->correct()->create(['question_id' => $question->id, 'position' => 0]);
        QuestionOption::factory()->create(['question_id' => $question->id, 'position' => 1]);
    }

    QuizSectionQuestion::create([
        'quiz_section_id' => $section->id,
        'question_id' => $question->id,
        'question_version' => $question->version,
        'position' => 0,
    ]);

    return [$quiz, $section, $question];
}

it('starts an attempt via the API and stores cursor', function () {
    [$quiz] = createQuizWithQuestion();
    $candidate = Candidate::factory()->verified()->create();
    $invitation = QuizInvitation::factory()->create([
        'quiz_id' => $quiz->id,
        'token' => 'token-123',
    ]);

    $response = $this->actingAs($candidate, 'candidate')
        ->withSession(['quiz_invitation_token' => $invitation->token])
        ->post('/quiz/start');

    $response->assertRedirect(route('candidate.quiz.run'));

    $attempt = QuizAttempt::first();
    expect($attempt)->not->toBeNull()
        ->and($attempt->status)->toBe(AttemptStatus::InProgress)
        ->and($attempt->current_section_id)->not->toBeNull()
        ->and($attempt->current_question_id)->not->toBeNull();
});

it('records a single-select answer', function () {
    [$quiz, , $question] = createQuizWithQuestion('single_select');
    $candidate = Candidate::factory()->verified()->create();
    $invitation = QuizInvitation::factory()->create(['quiz_id' => $quiz->id]);

    $attempt = app(StartQuizAttemptAction::class)->handle($quiz, $candidate, $invitation);
    $this->be($candidate, 'candidate');

    $option = $question->options()->first();

    $response = $this->withSession(['quiz_attempt_id' => $attempt->id])
        ->postJson('/quiz/answer', [
            'question_id' => $question->id,
            'option_ids' => [$option->id],
        ]);

    $response->assertOk();
    $attempt->refresh();

    expect($attempt->answers()->first()->selections)->toHaveCount(1);
});

it('records a coding submission', function () {
    [$quiz, , $question] = createQuizWithQuestion('coding');
    $candidate = Candidate::factory()->verified()->create();
    $invitation = QuizInvitation::factory()->create(['quiz_id' => $quiz->id]);

    $attempt = app(StartQuizAttemptAction::class)->handle($quiz, $candidate, $invitation);
    $this->be($candidate, 'candidate');

    $response = $this->withSession(['quiz_attempt_id' => $attempt->id])
        ->postJson('/quiz/answer', [
            'question_id' => $question->id,
            'code' => 'print(42)',
            'language' => 'python',
        ]);

    $response->assertOk();
    $attempt->refresh();

    $answer = $attempt->answers()->first();
    expect($answer->codingSubmission)->not->toBeNull()
        ->and($answer->codingSubmission->code)->toBe('print(42)');
});

it('advances to the next question', function () {
    [$quiz, $section, $firstQuestion] = createQuizWithQuestion('single_select');
    // Add second question
    $second = Question::factory()->singleSelect()->create(['version' => 1]);
    QuestionOption::factory()->create(['question_id' => $second->id, 'position' => 0]);
    QuestionOption::factory()->create(['question_id' => $second->id, 'position' => 1]);
    QuizSectionQuestion::create([
        'quiz_section_id' => $section->id,
        'question_id' => $second->id,
        'question_version' => $second->version,
        'position' => 1,
    ]);

    $candidate = Candidate::factory()->verified()->create();
    $invitation = QuizInvitation::factory()->create(['quiz_id' => $quiz->id]);
    $attempt = app(StartQuizAttemptAction::class)->handle($quiz->fresh('sections.sectionQuestions'), $candidate, $invitation);
    $this->be($candidate, 'candidate');

    $this->withSession(['quiz_attempt_id' => $attempt->id])
        ->postJson('/quiz/next-question')
        ->assertOk()
        ->assertJsonPath('question.id', $second->id);
});

it('submits an attempt', function () {
    [$quiz] = createQuizWithQuestion('single_select');
    $candidate = Candidate::factory()->verified()->create();
    $invitation = QuizInvitation::factory()->create(['quiz_id' => $quiz->id]);

    $attempt = app(StartQuizAttemptAction::class)->handle($quiz, $candidate, $invitation);
    $this->be($candidate, 'candidate');

    $this->withSession(['quiz_attempt_id' => $attempt->id])
        ->postJson('/quiz/submit')
        ->assertOk()
        ->assertJsonFragment(['submitted' => true]);

    expect($attempt->fresh()->status)->toBe(AttemptStatus::Submitted);
});
