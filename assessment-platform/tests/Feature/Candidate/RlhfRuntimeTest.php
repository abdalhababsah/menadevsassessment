<?php

use App\Contracts\AiProviders\AiResponseGenerator;
use App\Contracts\AiProviders\GeneratedResponse;
use App\Enums\AnswerStatus;
use App\Enums\QuizNavigationMode;
use App\Enums\RlhfTurnGenerationStatus;
use App\Enums\SelectedSide;
use App\Exceptions\AiRateLimitException;
use App\Jobs\Rlhf\GenerateRlhfTurnResponseJob;
use App\Models\AttemptAnswer;
use App\Models\AttemptRlhfEvaluation;
use App\Models\AttemptRlhfTurn;
use App\Models\Candidate;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizInvitation;
use App\Models\QuizSection;
use App\Models\QuizSectionQuestion;
use App\Models\RlhfCriterion;
use App\Models\RlhfGenerationJob;
use App\Models\RlhfQuestionConfig;
use App\Models\RlhfQuestionFormField;
use Illuminate\Support\Facades\Queue;

/**
 * @param  array<string, mixed>  $configOverrides
 * @return array{
 *     quiz: Quiz,
 *     section: QuizSection,
 *     question: Question,
 *     config: RlhfQuestionConfig,
 *     criterion: RlhfCriterion,
 * }
 */
function makeRlhfQuiz(array $configOverrides = []): array
{
    $quiz = Quiz::factory()->published()->create([
        'navigation_mode' => QuizNavigationMode::Free,
        'time_limit_seconds' => null,
    ]);

    $section = QuizSection::factory()->create([
        'quiz_id' => $quiz->id,
        'position' => 0,
    ]);

    $question = Question::factory()->rlhf()->create(['points' => 10]);

    $config = RlhfQuestionConfig::factory()->create(array_merge([
        'question_id' => $question->id,
        'number_of_turns' => 1,
        'model_a' => 'claude-test-a',
        'model_b' => 'claude-test-b',
        'enable_pre_prompt_form' => false,
        'enable_post_prompt_form' => false,
        'enable_rewrite_step' => false,
        'enable_post_rewrite_form' => false,
    ], $configOverrides));

    $criterion = RlhfCriterion::factory()->threePointQuality()->create([
        'question_id' => $question->id,
        'position' => 0,
    ]);

    RlhfQuestionFormField::factory()->postPrompt()->textarea()->create([
        'question_id' => $question->id,
        'field_key' => 'feedback',
        'position' => 0,
    ]);

    QuizSectionQuestion::create([
        'quiz_section_id' => $section->id,
        'question_id' => $question->id,
        'question_version' => 1,
        'position' => 0,
    ]);

    return [
        'quiz' => $quiz,
        'section' => $section,
        'question' => $question,
        'config' => $config,
        'criterion' => $criterion,
    ];
}

beforeEach(function () {
    $this->rlhf = makeRlhfQuiz();
    $this->candidate = Candidate::factory()->verified()->create();
    $this->invitation = QuizInvitation::factory()->create(['quiz_id' => $this->rlhf['quiz']->id]);

    $this->actingAs($this->candidate, 'candidate')
        ->withSession(['quiz_invitation_token' => $this->invitation->token]);

    $this->postJson('/quiz/start')->assertOk();
    $this->attempt = QuizAttempt::query()->latest('id')->firstOrFail();
});

describe('RLHF show endpoint', function () {
    test('renders the runner and auto-creates the first turn', function () {
        $this->get('/quiz/rlhf')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Candidate/Quiz/Rlhf/Runner')
                ->where('state.question.id', $this->rlhf['question']->id)
                ->where('state.question.number_of_turns', 1)
                ->where('state.current_step', 'prompt_input')
                ->has('state.current_turn'),
            );

        $answer = $this->attempt->answers()->where('question_id', $this->rlhf['question']->id)->firstOrFail();
        expect($answer->rlhfTurns)->toHaveCount(1);
    });

    test('visiting twice does not create duplicate turns', function () {
        $this->get('/quiz/rlhf');
        $this->get('/quiz/rlhf');

        $answer = $this->attempt->answers()->where('question_id', $this->rlhf['question']->id)->firstOrFail();
        expect($answer->rlhfTurns)->toHaveCount(1);
    });

    test('the main runner redirects into the dedicated RLHF runner', function () {
        $this->get('/quiz/run')
            ->assertRedirect('/quiz/rlhf');
    });
});

describe('submitting prompt input', function () {
    test('stores input, dispatches jobs and advances step to response_pair', function () {
        Queue::fake();

        $this->get('/quiz/rlhf');

        $response = $this->postJson('/quiz/rlhf/prompt-input', [
            'input' => 'Explain photosynthesis in simple terms.',
        ])->assertOk();

        Queue::assertPushed(GenerateRlhfTurnResponseJob::class, 2);

        $response->assertJsonPath('state.current_step', 'response_pair');

        $turn = AttemptRlhfTurn::query()->latest('id')->firstOrFail();
        expect($turn->candidate_input)->toBe('Explain photosynthesis in simple terms.')
            ->and($turn->generation_status)->toBe(RlhfTurnGenerationStatus::Generating);

        expect(RlhfGenerationJob::query()->where('rlhf_turn_id', $turn->id)->count())->toBe(2);
    });
});

describe('polling generation status', function () {
    test('returns current turn + generation status', function () {
        $this->get('/quiz/rlhf');

        $this->getJson('/quiz/rlhf/generation-status')
            ->assertOk()
            ->assertJsonPath('current_step', 'prompt_input')
            ->assertJsonPath('responses_ready', false);
    });
});

describe('submitting evaluations', function () {
    test('requires all criteria and then advances past evaluate_a', function () {
        Queue::fake();

        $this->get('/quiz/rlhf');
        $this->postJson('/quiz/rlhf/prompt-input', ['input' => 'hi'])->assertOk();

        // Force both responses ready
        $turn = AttemptRlhfTurn::query()->latest('id')->firstOrFail();
        $turn->update([
            'response_a' => 'response A text',
            'response_b' => 'response B text',
            'generation_status' => RlhfTurnGenerationStatus::Ready,
        ]);

        $this->postJson('/quiz/rlhf/evaluation', [
            'response_side' => 'a',
            'evaluations' => [[
                'criterion_id' => $this->rlhf['criterion']->id,
                'rating_value' => '3',
                'justification' => 'Excellent',
            ]],
        ])->assertOk()->assertJsonPath('state.current_step', 'evaluate_b');

        expect($turn->evaluations()->count())->toBe(1)
            ->and($turn->evaluations()->first()->rating_value)->toBe('3')
            ->and($turn->evaluations()->first()->response_side)->toBe('a');
    });

    test('rejects evaluations that do not cover every criterion', function () {
        Queue::fake();
        // Add a second criterion so the submitted payload is incomplete.
        RlhfCriterion::factory()->threePointQuality()->create([
            'question_id' => $this->rlhf['question']->id,
            'position' => 1,
        ]);

        $this->get('/quiz/rlhf');
        $this->postJson('/quiz/rlhf/prompt-input', ['input' => 'hi']);
        $turn = AttemptRlhfTurn::query()->latest('id')->firstOrFail();
        $turn->update([
            'response_a' => 'a',
            'response_b' => 'b',
            'generation_status' => RlhfTurnGenerationStatus::Ready,
        ]);

        $this->postJson('/quiz/rlhf/evaluation', [
            'response_side' => 'a',
            'evaluations' => [[
                'criterion_id' => $this->rlhf['criterion']->id,
                'rating_value' => '3',
                'justification' => null,
            ]],
        ])->assertStatus(422);
    });

    test('rejects invalid rating values for a criterion scale', function () {
        Queue::fake();

        $this->get('/quiz/rlhf');
        $this->postJson('/quiz/rlhf/prompt-input', ['input' => 'hi']);
        $turn = AttemptRlhfTurn::query()->latest('id')->firstOrFail();
        $turn->update([
            'response_a' => 'a',
            'response_b' => 'b',
            'generation_status' => RlhfTurnGenerationStatus::Ready,
        ]);

        $this->postJson('/quiz/rlhf/evaluation', [
            'response_side' => 'a',
            'evaluations' => [[
                'criterion_id' => $this->rlhf['criterion']->id,
                'rating_value' => '99',
                'justification' => null,
            ]],
        ])->assertStatus(422);
    });
});

describe('submitting SxS rating', function () {
    test('stores rating, justification and derives selected side', function () {
        Queue::fake();

        $this->get('/quiz/rlhf');
        $this->postJson('/quiz/rlhf/prompt-input', ['input' => 'hi']);

        $turn = AttemptRlhfTurn::query()->latest('id')->firstOrFail();
        $turn->update([
            'response_a' => 'a',
            'response_b' => 'b',
            'generation_status' => RlhfTurnGenerationStatus::Ready,
        ]);

        // Complete both sides before SxS
        foreach (['a', 'b'] as $side) {
            $this->postJson('/quiz/rlhf/evaluation', [
                'response_side' => $side,
                'evaluations' => [[
                    'criterion_id' => $this->rlhf['criterion']->id,
                    'rating_value' => '3',
                    'justification' => 'Balanced and acceptable.',
                ]],
            ]);
        }

        $this->postJson('/quiz/rlhf/sxs-rating', [
            'rating' => 2,
            'justification' => 'Response A was more direct.',
        ])->assertOk();

        $turn->refresh();
        expect($turn->sxs_rating)->toBe(2)
            ->and($turn->selected_side)->toBe(SelectedSide::A);
    });
});

describe('advancing turns', function () {
    test('single-turn quiz marks answer Answered when ready to advance', function () {
        Queue::fake();

        $this->get('/quiz/rlhf');
        $this->postJson('/quiz/rlhf/prompt-input', ['input' => 'hi']);

        $turn = AttemptRlhfTurn::query()->latest('id')->firstOrFail();
        $turn->update([
            'response_a' => 'a',
            'response_b' => 'b',
            'generation_status' => RlhfTurnGenerationStatus::Ready,
        ]);

        foreach (['a', 'b'] as $side) {
            $this->postJson('/quiz/rlhf/evaluation', [
                'response_side' => $side,
                'evaluations' => [[
                    'criterion_id' => $this->rlhf['criterion']->id,
                    'rating_value' => '3',
                    'justification' => 'Balanced and acceptable.',
                ]],
            ]);
        }

        $this->postJson('/quiz/rlhf/sxs-rating', [
            'rating' => 4,
            'justification' => 'Equal.',
        ])->assertOk()->assertJsonPath('state.current_step', 'turn_complete');

        $this->postJson('/quiz/rlhf/turn/advance')->assertOk();

        $answer = $this->attempt->answers()->where('question_id', $this->rlhf['question']->id)->firstOrFail();
        expect($answer->status)->toBe(AnswerStatus::Answered);
    });
});

describe('multi-turn context building', function () {
    test('buildMessages passes only selected prior responses to the AI provider', function () {
        $this->rlhf['config']->update(['number_of_turns' => 3]);

        $answer = $this->attempt->answers()->where('question_id', $this->rlhf['question']->id)->firstOrFail();

        AttemptRlhfTurn::factory()->completed()->create([
            'attempt_answer_id' => $answer->id,
            'turn_number' => 1,
            'response_a' => 'turn1 response A',
            'response_b' => 'turn1 response B',
            'selected_side' => SelectedSide::A,
            'candidate_input' => 'first user input',
        ]);

        AttemptRlhfTurn::factory()->completed()->create([
            'attempt_answer_id' => $answer->id,
            'turn_number' => 2,
            'response_a' => 'turn2 response A',
            'response_b' => 'turn2 response B',
            'selected_side' => SelectedSide::B,
            'candidate_input' => 'second user input',
        ]);

        $turn3 = AttemptRlhfTurn::factory()->create([
            'attempt_answer_id' => $answer->id,
            'turn_number' => 3,
            'candidate_input' => 'third user input',
        ]);

        $job = new GenerateRlhfTurnResponseJob($turn3->id, 'a');
        $messages = $job->buildMessages($turn3->fresh());

        $assistantContents = collect($messages)
            ->where('role', 'assistant')
            ->pluck('content')
            ->all();

        expect($assistantContents)->toContain('turn1 response A')
            ->and($assistantContents)->toContain('turn2 response B')
            ->and($assistantContents)->not->toContain('turn1 response B')
            ->and($assistantContents)->not->toContain('turn2 response A');

        $lastUser = collect($messages)->last();
        expect($lastUser['role'])->toBe('user')
            ->and($lastUser['content'])->toBe('third user input');
    });
});

describe('generation job success + idempotency', function () {
    test('job sets response column and flips status once both sides complete', function () {
        Queue::fake();

        $this->get('/quiz/rlhf');
        $this->postJson('/quiz/rlhf/prompt-input', ['input' => 'hi']);
        $turn = AttemptRlhfTurn::query()->latest('id')->firstOrFail();

        $this->app->instance(AiResponseGenerator::class, new class implements AiResponseGenerator
        {
            public function generate(array $messages, string $model, array $params = []): GeneratedResponse
            {
                return new GeneratedResponse(
                    content: 'generated for '.$model,
                    model: $model,
                    inputTokens: 1,
                    outputTokens: 2,
                );
            }
        });

        (new GenerateRlhfTurnResponseJob($turn->id, 'a'))->handle(app(AiResponseGenerator::class));
        $turn->refresh();
        expect($turn->response_a)->toContain('claude-test-a')
            ->and($turn->bothResponsesReady())->toBeFalse();

        (new GenerateRlhfTurnResponseJob($turn->id, 'b'))->handle(app(AiResponseGenerator::class));
        $turn->refresh();
        expect($turn->response_b)->toContain('claude-test-b')
            ->and($turn->bothResponsesReady())->toBeTrue()
            ->and($turn->generation_status)->toBe(RlhfTurnGenerationStatus::Ready);

        // Idempotency: rerunning should not double-populate.
        (new GenerateRlhfTurnResponseJob($turn->id, 'a'))->handle(app(AiResponseGenerator::class));
        $turn->refresh();
        expect($turn->response_a)->toContain('claude-test-a');
    });

    test('AiRateLimitException re-raises so queue backoff handles retry', function () {
        Queue::fake();

        $this->get('/quiz/rlhf');
        $this->postJson('/quiz/rlhf/prompt-input', ['input' => 'hi']);
        $turn = AttemptRlhfTurn::query()->latest('id')->firstOrFail();

        $this->app->instance(AiResponseGenerator::class, new class implements AiResponseGenerator
        {
            public function generate(array $messages, string $model, array $params = []): GeneratedResponse
            {
                throw new AiRateLimitException('rate limited');
            }
        });

        expect(fn () => (new GenerateRlhfTurnResponseJob($turn->id, 'a'))->handle(app(AiResponseGenerator::class)))
            ->toThrow(AiRateLimitException::class);
    });
});

describe('full RLHF flow with forms and rewrite', function () {
    test('completes every step in order for a configured single-turn question', function () {
        $rlhf = makeRlhfQuiz([
            'enable_pre_prompt_form' => true,
            'enable_post_prompt_form' => true,
            'enable_rewrite_step' => true,
            'enable_post_rewrite_form' => true,
        ]);

        RlhfQuestionFormField::factory()->prePrompt()->radio()->create([
            'question_id' => $rlhf['question']->id,
            'field_key' => 'intent',
            'position' => 0,
        ]);
        RlhfQuestionFormField::factory()->postRewrite()->textarea()->create([
            'question_id' => $rlhf['question']->id,
            'field_key' => 'confidence',
            'position' => 0,
        ]);

        $candidate = Candidate::factory()->verified()->create();
        $invitation = QuizInvitation::factory()->create(['quiz_id' => $rlhf['quiz']->id]);

        $this->actingAs($candidate, 'candidate')
            ->withSession(['quiz_invitation_token' => $invitation->token]);

        $this->postJson('/quiz/start')->assertOk();
        $attempt = QuizAttempt::query()->latest('id')->firstOrFail();

        $this->withSession(['quiz_attempt_id' => $attempt->id])
            ->get('/quiz/rlhf')
            ->assertInertia(fn ($page) => $page->where('state.current_step', 'pre_prompt_form'));

        $this->withSession(['quiz_attempt_id' => $attempt->id])
            ->postJson('/quiz/rlhf/form', [
                'stage' => 'pre_prompt',
                'responses' => ['intent' => 'Option A'],
            ])->assertOk()->assertJsonPath('state.current_step', 'prompt_input');

        Queue::fake();

        $this->withSession(['quiz_attempt_id' => $attempt->id])
            ->postJson('/quiz/rlhf/prompt-input', [
                'input' => 'Write a compact answer.',
            ])->assertOk();

        Queue::assertPushed(GenerateRlhfTurnResponseJob::class, 2);

        $turn = AttemptRlhfTurn::query()->latest('id')->firstOrFail();
        $turn->update([
            'response_a' => 'First candidate response',
            'response_b' => 'Second candidate response',
            'generation_status' => RlhfTurnGenerationStatus::Ready,
        ]);

        $this->withSession(['quiz_attempt_id' => $attempt->id])
            ->postJson('/quiz/rlhf/form', [
                'stage' => 'post_prompt',
                'responses' => ['feedback' => str_repeat('Initial reaction. ', 4)],
            ])->assertOk()->assertJsonPath('state.current_step', 'evaluate_a');

        foreach (['a', 'b'] as $side) {
            $this->withSession(['quiz_attempt_id' => $attempt->id])
                ->postJson('/quiz/rlhf/evaluation', [
                    'response_side' => $side,
                    'evaluations' => [[
                        'criterion_id' => $rlhf['criterion']->id,
                        'rating_value' => '3',
                        'justification' => 'Looks good',
                    ]],
                ])->assertOk();
        }

        $this->withSession(['quiz_attempt_id' => $attempt->id])
            ->postJson('/quiz/rlhf/sxs-rating', [
                'rating' => 6,
                'justification' => 'B is stronger.',
            ])->assertOk()->assertJsonPath('state.current_step', 'rewrite');

        $this->withSession(['quiz_attempt_id' => $attempt->id])
            ->postJson('/quiz/rlhf/rewrite', [
                'rewrite' => 'Refined version of response B',
            ])->assertOk()->assertJsonPath('state.current_step', 'post_rewrite_form');

        $this->withSession(['quiz_attempt_id' => $attempt->id])
            ->postJson('/quiz/rlhf/form', [
                'stage' => 'post_rewrite',
                'responses' => ['confidence' => str_repeat('Ready to ship. ', 4)],
            ])->assertOk()->assertJsonPath('state.current_step', 'turn_complete');

        $this->withSession(['quiz_attempt_id' => $attempt->id])
            ->postJson('/quiz/rlhf/turn/advance')
            ->assertOk()
            ->assertJsonPath('state.current_step', 'completed')
            ->assertJsonPath('state.question_completed', true);
    });

    test('rejects invalid form field submissions', function () {
        $rlhf = makeRlhfQuiz([
            'enable_pre_prompt_form' => true,
        ]);

        RlhfQuestionFormField::factory()->prePrompt()->radio()->create([
            'question_id' => $rlhf['question']->id,
            'field_key' => 'intent',
            'position' => 0,
        ]);

        $candidate = Candidate::factory()->verified()->create();
        $invitation = QuizInvitation::factory()->create(['quiz_id' => $rlhf['quiz']->id]);

        $this->actingAs($candidate, 'candidate')
            ->withSession(['quiz_invitation_token' => $invitation->token]);

        $this->postJson('/quiz/start')->assertOk();
        $attempt = QuizAttempt::query()->latest('id')->firstOrFail();

        $this->withSession(['quiz_attempt_id' => $attempt->id])
            ->get('/quiz/rlhf');

        $this->withSession(['quiz_attempt_id' => $attempt->id])
            ->postJson('/quiz/rlhf/form', [
                'stage' => 'pre_prompt',
                'responses' => ['intent' => 'Invalid option'],
            ])
            ->assertStatus(422);
    });
});

describe('message context hygiene', function () {
    test('evaluations and justifications never appear in the model message context', function () {
        $this->rlhf['config']->update(['number_of_turns' => 2]);
        $answer = $this->attempt->answers()->where('question_id', $this->rlhf['question']->id)->firstOrFail();

        $turn1 = AttemptRlhfTurn::factory()->completed()->create([
            'attempt_answer_id' => $answer->id,
            'turn_number' => 1,
            'response_a' => 'selected response',
            'response_b' => 'rejected response',
            'selected_side' => SelectedSide::A,
            'candidate_input' => 'prior user prompt',
        ]);

        AttemptRlhfEvaluation::factory()->create([
            'rlhf_turn_id' => $turn1->id,
            'criterion_id' => $this->rlhf['criterion']->id,
            'response_side' => 'a',
            'rating_value' => '1',
            'justification' => 'evaluation text that must not leak',
        ]);

        $turn2 = AttemptRlhfTurn::factory()->create([
            'attempt_answer_id' => $answer->id,
            'turn_number' => 2,
            'candidate_input' => 'current prompt',
        ]);

        $messages = (new GenerateRlhfTurnResponseJob($turn2->id, 'a'))->buildMessages($turn2->fresh());
        $flattened = collect($messages)->pluck('content')->implode("\n");

        expect($flattened)->toContain('selected response')
            ->and($flattened)->not->toContain('rejected response')
            ->and($flattened)->not->toContain('evaluation text that must not leak');
    });
});

describe('job retry policy', function () {
    test('declares the expected retry, timeout, and backoff settings', function () {
        $job = new GenerateRlhfTurnResponseJob(1, 'a');

        expect($job->tries)->toBe(5)
            ->and($job->timeout)->toBe(90)
            ->and($job->backoff())->toBe([5, 15, 30, 60, 120]);
    });
});

describe('high-volume generation simulation', function () {
    test('simulates 200 generation jobs without duplicating or failing', function () {
        $question = Question::factory()->rlhf()->create();
        RlhfQuestionConfig::factory()->create([
            'question_id' => $question->id,
            'model_a' => 'claude-load-a',
            'model_b' => 'claude-load-b',
        ]);

        $answer = AttemptAnswer::factory()->create([
            'question_id' => $question->id,
        ]);

        $this->app->instance(AiResponseGenerator::class, new class implements AiResponseGenerator
        {
            public function generate(array $messages, string $model, array $params = []): GeneratedResponse
            {
                return new GeneratedResponse(
                    content: 'payload for '.$model,
                    model: $model,
                    inputTokens: 10,
                    outputTokens: 20,
                );
            }
        });

        for ($index = 1; $index <= 100; $index++) {
            AttemptRlhfTurn::factory()->create([
                'attempt_answer_id' => $answer->id,
                'turn_number' => $index,
                'candidate_input' => 'Prompt '.$index,
                'generation_status' => RlhfTurnGenerationStatus::Generating,
            ]);
        }

        AttemptRlhfTurn::query()->each(function (AttemptRlhfTurn $turn): void {
            RlhfGenerationJob::factory()->create([
                'rlhf_turn_id' => $turn->id,
                'side' => 'a',
            ]);
            RlhfGenerationJob::factory()->create([
                'rlhf_turn_id' => $turn->id,
                'side' => 'b',
            ]);

            (new GenerateRlhfTurnResponseJob($turn->id, 'a'))->handle(app(AiResponseGenerator::class));
            (new GenerateRlhfTurnResponseJob($turn->id, 'b'))->handle(app(AiResponseGenerator::class));
        });

        expect(AttemptRlhfTurn::query()->whereNull('response_a')->count())->toBe(0)
            ->and(AttemptRlhfTurn::query()->whereNull('response_b')->count())->toBe(0)
            ->and(AttemptRlhfTurn::query()->where('generation_status', RlhfTurnGenerationStatus::Ready)->count())->toBe(100);
    });
});
