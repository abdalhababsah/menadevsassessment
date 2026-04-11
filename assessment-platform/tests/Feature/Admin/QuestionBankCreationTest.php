<?php

use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizSection;
use App\Models\QuizSectionQuestion;
use App\Models\User;
use Database\Seeders\PermissionSeeder;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->admin = User::factory()->superAdmin()->create();
});

describe('standalone question bank creation', function () {
    test('admin can create a single-select question from the question bank without a quiz', function () {
        $payload = [
            'stem' => 'Bank single-select stem',
            'instructions' => null,
            'difficulty' => 'easy',
            'points' => 2,
            'tags' => [],
            'options' => [
                ['content' => 'A', 'content_type' => 'text', 'is_correct' => false, 'position' => 0],
                ['content' => 'B', 'content_type' => 'text', 'is_correct' => true, 'position' => 1],
            ],
        ];

        $this->actingAs($this->admin)
            ->post(route('admin.questions.store.single-select'), $payload)
            ->assertRedirect(route('admin.questions.index'));

        $question = Question::query()->latest('id')->firstOrFail();
        expect($question->type)->toBe(QuestionType::SingleSelect)
            ->and($question->stem)->toBe('Bank single-select stem')
            ->and($question->options)->toHaveCount(2);

        // No section attachment when creating from the bank.
        expect(QuizSectionQuestion::query()->where('question_id', $question->id)->count())->toBe(0);
    });

    test('admin can create a multi-select question from the bank standalone', function () {
        $payload = [
            'stem' => 'Bank multi stem',
            'difficulty' => 'medium',
            'points' => 3,
            'tags' => [],
            'options' => [
                ['content' => 'A', 'content_type' => 'text', 'is_correct' => true, 'position' => 0],
                ['content' => 'B', 'content_type' => 'text', 'is_correct' => true, 'position' => 1],
                ['content' => 'C', 'content_type' => 'text', 'is_correct' => false, 'position' => 2],
            ],
        ];

        $this->actingAs($this->admin)
            ->post(route('admin.questions.store.multi-select'), $payload)
            ->assertRedirect(route('admin.questions.index'));

        $question = Question::query()->latest('id')->firstOrFail();
        expect($question->type)->toBe(QuestionType::MultiSelect)
            ->and($question->options->where('is_correct', true)->count())->toBe(2);

        expect(QuizSectionQuestion::query()->where('question_id', $question->id)->count())->toBe(0);
    });

    test('admin can create a coding question from the bank standalone', function () {
        $payload = [
            'stem' => 'Bank coding stem',
            'difficulty' => 'hard',
            'points' => 5,
            'tags' => [],
            'allowed_languages' => ['python'],
            'starter_code' => null,
            'time_limit_ms' => 2000,
            'memory_limit_mb' => 128,
            'test_cases' => [
                [
                    'input' => 'input',
                    'expected_output' => 'hello',
                    'is_hidden' => false,
                    'weight' => 1,
                ],
            ],
        ];

        $this->actingAs($this->admin)
            ->post(route('admin.questions.store.coding'), $payload)
            ->assertRedirect(route('admin.questions.index'));

        $question = Question::query()->latest('id')->firstOrFail();
        expect($question->type)->toBe(QuestionType::Coding)
            ->and($question->codingConfig)->not->toBeNull()
            ->and($question->testCases)->toHaveCount(1);

        expect(QuizSectionQuestion::query()->where('question_id', $question->id)->count())->toBe(0);
    });

    test('admin can create an RLHF question from the bank standalone', function () {
        $payload = [
            'stem' => 'Bank rlhf stem',
            'difficulty' => 'medium',
            'points' => 4,
            'tags' => [],
            'number_of_turns' => 2,
            'candidate_input_mode' => 'text',
            'model_a' => 'claude-sonnet-4-6',
            'model_b' => 'claude-opus-4-6',
            'generation_params' => null,
            'enable_pre_prompt_form' => false,
            'enable_post_prompt_form' => false,
            'enable_rewrite_step' => false,
            'enable_post_rewrite_form' => false,
            'guidelines_markdown' => null,
            'criteria' => [
                [
                    'name' => 'Clarity',
                    'description' => 'How clear is the response',
                    'scale_type' => 'five_point_centered',
                    'scale_labels' => [
                        '1' => 'Very unclear',
                        '2' => 'Unclear',
                        '3' => 'Neutral',
                        '4' => 'Clear',
                        '5' => 'Very clear',
                    ],
                    'justification_required_when' => [],
                    'position' => 0,
                ],
            ],
            'form_fields' => [],
        ];

        $this->actingAs($this->admin)
            ->post(route('admin.questions.store.rlhf'), $payload)
            ->assertRedirect(route('admin.questions.index'));

        $question = Question::query()->latest('id')->firstOrFail();
        expect($question->type)->toBe(QuestionType::Rlhf)
            ->and($question->rlhfConfig)->not->toBeNull()
            ->and($question->rlhfCriteria)->toHaveCount(1);

        expect(QuizSectionQuestion::query()->where('question_id', $question->id)->count())->toBe(0);
    });

    test('standalone store still accepts an explicit quiz_section_id and attaches the question', function () {
        $quiz = Quiz::factory()->create();
        $section = QuizSection::factory()->create(['quiz_id' => $quiz->id, 'position' => 0]);

        $payload = [
            'stem' => 'Standalone with section id',
            'difficulty' => 'easy',
            'points' => 1,
            'tags' => [],
            'quiz_section_id' => $section->id,
            'options' => [
                ['content' => 'A', 'content_type' => 'text', 'is_correct' => true, 'position' => 0],
                ['content' => 'B', 'content_type' => 'text', 'is_correct' => false, 'position' => 1],
            ],
        ];

        $this->actingAs($this->admin)
            ->post(route('admin.questions.store.single-select'), $payload)
            ->assertRedirect(route('admin.questions.index'));

        $question = Question::query()->latest('id')->firstOrFail();
        expect(
            QuizSectionQuestion::query()
                ->where('quiz_section_id', $section->id)
                ->where('question_id', $question->id)
                ->exists()
        )->toBeTrue();
    });
});

describe('inline question creation regression', function () {
    test('admin can still create a question inline from the quiz builder and it attaches to the section', function () {
        $quiz = Quiz::factory()->create();
        $section = QuizSection::factory()->create(['quiz_id' => $quiz->id, 'position' => 0]);

        $payload = [
            'stem' => 'Inline from builder',
            'difficulty' => 'easy',
            'points' => 1,
            'tags' => [],
            'options' => [
                ['content' => 'A', 'content_type' => 'text', 'is_correct' => true, 'position' => 0],
                ['content' => 'B', 'content_type' => 'text', 'is_correct' => false, 'position' => 1],
            ],
        ];

        $this->actingAs($this->admin)
            ->post(
                route('admin.quizzes.sections.questions.inline.single-select', [$quiz, $section]),
                $payload,
            )
            ->assertRedirect();

        $question = Question::query()->latest('id')->firstOrFail();
        expect($question->stem)->toBe('Inline from builder');

        $pivot = QuizSectionQuestion::query()
            ->where('quiz_section_id', $section->id)
            ->where('question_id', $question->id)
            ->firstOrFail();
        expect($pivot->position)->toBe(0);
    });

    test('inline coding creation still persists coding config + test cases and attaches', function () {
        $quiz = Quiz::factory()->create();
        $section = QuizSection::factory()->create(['quiz_id' => $quiz->id, 'position' => 0]);

        $payload = [
            'stem' => 'Inline coding',
            'difficulty' => 'medium',
            'points' => 5,
            'tags' => [],
            'allowed_languages' => ['python'],
            'starter_code' => null,
            'time_limit_ms' => 3000,
            'memory_limit_mb' => 256,
            'test_cases' => [
                [
                    'input' => 'in',
                    'expected_output' => 'out',
                    'is_hidden' => false,
                    'weight' => 1,
                ],
            ],
        ];

        $this->actingAs($this->admin)
            ->post(
                route('admin.quizzes.sections.questions.inline.coding', [$quiz, $section]),
                $payload,
            )
            ->assertRedirect();

        $question = Question::query()->latest('id')->firstOrFail();
        expect($question->type)->toBe(QuestionType::Coding)
            ->and($question->codingConfig)->not->toBeNull()
            ->and($question->testCases)->toHaveCount(1);

        expect(
            QuizSectionQuestion::query()
                ->where('quiz_section_id', $section->id)
                ->where('question_id', $question->id)
                ->exists()
        )->toBeTrue();
    });
});

describe('create page route normalization', function () {
    test('GET /admin/questions/create/single-select (hyphenated) renders the create page', function () {
        $this->actingAs($this->admin)
            ->get('/admin/questions/create/single-select')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/QuestionBank/Create/SingleSelect'));
    });

    test('GET /admin/questions/create/single_select (underscore) still renders the create page', function () {
        $this->actingAs($this->admin)
            ->get('/admin/questions/create/single_select')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/QuestionBank/Create/SingleSelect'));
    });

    test('GET /admin/questions/create/multi-select (hyphenated) renders the create page', function () {
        $this->actingAs($this->admin)
            ->get('/admin/questions/create/multi-select')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/QuestionBank/Create/MultiSelect'));
    });

    test('GET /admin/questions/create/garbage returns 404', function () {
        $this->actingAs($this->admin)
            ->get('/admin/questions/create/garbage')
            ->assertNotFound();
    });
});
