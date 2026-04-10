<?php

use App\Enums\QuestionType;
use App\Enums\RlhfFieldType;
use App\Enums\RlhfFormStage;
use App\Enums\RlhfScaleType;
use App\Models\AuditLog;
use App\Models\Question;
use App\Models\User;
use Database\Seeders\PermissionSeeder;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->admin = User::factory()->superAdmin()->create();
});

/**
 * @return array<string, mixed>
 */
function validRlhfPayload(array $overrides = []): array
{
    return array_merge([
        'stem' => 'Compare these two model responses',
        'instructions' => 'Rate each response on the listed criteria',
        'difficulty' => 'medium',
        'points' => 5,
        'tags' => [],
        'number_of_turns' => 2,
        'candidate_input_mode' => 'text',
        'model_a' => 'claude-sonnet-4-5-20250514',
        'model_b' => 'gpt-4o',
        'generation_params' => ['temperature' => 0.7],
        'enable_pre_prompt_form' => false,
        'enable_post_prompt_form' => true,
        'enable_rewrite_step' => false,
        'enable_post_rewrite_form' => false,
        'guidelines_markdown' => '# Guidelines',
        'criteria' => [
            [
                'name' => 'Helpfulness',
                'description' => 'How helpful was the response',
                'scale_type' => 'three_point_quality',
                'scale_labels' => ['Poor', 'Acceptable', 'Excellent'],
                'justification_required_when' => [1],
                'position' => 0,
            ],
            [
                'name' => 'Accuracy',
                'description' => 'How accurate the response was',
                'scale_type' => 'five_point_centered',
                'scale_labels' => ['Much worse', 'Worse', 'Same', 'Better', 'Much better'],
                'justification_required_when' => [],
                'position' => 1,
            ],
            [
                'name' => 'Safety',
                'description' => 'Whether the response is safe',
                'scale_type' => 'three_point_quality',
                'scale_labels' => ['Unsafe', 'Borderline', 'Safe'],
                'justification_required_when' => [1],
                'position' => 2,
            ],
            [
                'name' => 'Clarity',
                'description' => 'How clear the response is',
                'scale_type' => 'three_point_quality',
                'scale_labels' => ['Confusing', 'Okay', 'Crystal clear'],
                'justification_required_when' => [],
                'position' => 3,
            ],
        ],
        'form_fields' => [
            [
                'stage' => 'pre_prompt',
                'field_key' => 'task_clarity',
                'label' => 'How clear is the task?',
                'description' => null,
                'field_type' => 'radio',
                'options' => ['Unclear', 'Somewhat', 'Very clear'],
                'required' => true,
                'min_length' => null,
                'position' => 0,
            ],
            [
                'stage' => 'pre_prompt',
                'field_key' => 'initial_thoughts',
                'label' => 'Initial thoughts',
                'description' => null,
                'field_type' => 'textarea',
                'options' => null,
                'required' => true,
                'min_length' => 20,
                'position' => 1,
            ],
            [
                'stage' => 'post_prompt',
                'field_key' => 'response_quality',
                'label' => 'Overall response quality',
                'description' => null,
                'field_type' => 'radio',
                'options' => ['Bad', 'Okay', 'Good'],
                'required' => true,
                'min_length' => null,
                'position' => 0,
            ],
            [
                'stage' => 'post_prompt',
                'field_key' => 'justification',
                'label' => 'Justify your rating',
                'description' => null,
                'field_type' => 'textarea',
                'options' => null,
                'required' => true,
                'min_length' => 30,
                'position' => 1,
            ],
            [
                'stage' => 'post_rewrite',
                'field_key' => 'rewrite_improvement',
                'label' => 'Did the rewrite improve things?',
                'description' => null,
                'field_type' => 'dropdown',
                'options' => ['Yes', 'Somewhat', 'No'],
                'required' => true,
                'min_length' => null,
                'position' => 0,
            ],
            [
                'stage' => 'post_rewrite',
                'field_key' => 'rewrite_notes',
                'label' => 'Additional notes',
                'description' => null,
                'field_type' => 'text',
                'options' => null,
                'required' => false,
                'min_length' => null,
                'position' => 1,
            ],
        ],
    ], $overrides);
}

describe('create RLHF question', function () {
    test('creates an RLHF question with config, criteria, and form fields', function () {
        $this->actingAs($this->admin)
            ->post(route('admin.questions.store.rlhf'), validRlhfPayload())
            ->assertRedirect(route('admin.questions.index'));

        $question = Question::with(['rlhfConfig', 'rlhfCriteria', 'rlhfFormFields'])
            ->latest('id')
            ->first();

        expect($question)
            ->not->toBeNull()
            ->type->toBe(QuestionType::Rlhf);

        expect($question->rlhfConfig)
            ->not->toBeNull()
            ->number_of_turns->toBe(2)
            ->model_a->toBe('claude-sonnet-4-5-20250514')
            ->enable_post_prompt_form->toBeTrue();

        expect($question->rlhfCriteria)->toHaveCount(4);
        expect($question->rlhfFormFields)->toHaveCount(6);

        // Verify form fields distributed across 3 stages
        expect($question->rlhfFormFields->where('stage', RlhfFormStage::PrePrompt)->count())->toBe(2);
        expect($question->rlhfFormFields->where('stage', RlhfFormStage::PostPrompt)->count())->toBe(2);
        expect($question->rlhfFormFields->where('stage', RlhfFormStage::PostRewrite)->count())->toBe(2);
    });

    test('rejects fewer than 1 criterion', function () {
        $this->actingAs($this->admin)
            ->post(route('admin.questions.store.rlhf'), validRlhfPayload(['criteria' => []]))
            ->assertSessionHasErrors('criteria');
    });

    test('rejects mismatched scale_labels length for three-point scale', function () {
        $this->actingAs($this->admin)
            ->post(route('admin.questions.store.rlhf'), validRlhfPayload([
                'criteria' => [
                    [
                        'name' => 'Bad scale',
                        'description' => 'desc',
                        'scale_type' => 'three_point_quality',
                        'scale_labels' => ['Only', 'Two'],
                        'justification_required_when' => [],
                        'position' => 0,
                    ],
                ],
            ]))
            ->assertSessionHasErrors('criteria.0.scale_labels');
    });

    test('rejects mismatched scale_labels length for five-point scale', function () {
        $this->actingAs($this->admin)
            ->post(route('admin.questions.store.rlhf'), validRlhfPayload([
                'criteria' => [
                    [
                        'name' => 'Bad scale',
                        'description' => 'desc',
                        'scale_type' => 'five_point_centered',
                        'scale_labels' => ['One', 'Two', 'Three'],
                        'justification_required_when' => [],
                        'position' => 0,
                    ],
                ],
            ]))
            ->assertSessionHasErrors('criteria.0.scale_labels');
    });

    test('rejects number_of_turns above 10', function () {
        $this->actingAs($this->admin)
            ->post(route('admin.questions.store.rlhf'), validRlhfPayload(['number_of_turns' => 11]))
            ->assertSessionHasErrors('number_of_turns');
    });

    test('rejects number_of_turns below 1', function () {
        $this->actingAs($this->admin)
            ->post(route('admin.questions.store.rlhf'), validRlhfPayload(['number_of_turns' => 0]))
            ->assertSessionHasErrors('number_of_turns');
    });

    test('creating RLHF question creates audit log', function () {
        $this->actingAs($this->admin)
            ->post(route('admin.questions.store.rlhf'), validRlhfPayload());

        $log = AuditLog::where('action', 'question.created')->latest('id')->first();
        expect($log)->not->toBeNull()
            ->and($log->changes['type'])->toBe('rlhf');
    });
});

describe('update RLHF question', function () {
    test('updates an RLHF question in place', function () {
        $this->actingAs($this->admin)
            ->post(route('admin.questions.store.rlhf'), validRlhfPayload(['stem' => 'Original']));

        $question = Question::latest('id')->first();

        $this->actingAs($this->admin)
            ->put(route('admin.questions.update.rlhf', $question), validRlhfPayload([
                'stem' => 'Updated stem',
                'number_of_turns' => 3,
            ]))
            ->assertRedirect();

        $question->refresh()->load(['rlhfConfig', 'rlhfCriteria']);
        expect($question->stem)->toBe('Updated stem')
            ->and($question->rlhfConfig->number_of_turns)->toBe(3);
    });

    test('reorders criteria when updating', function () {
        $this->actingAs($this->admin)
            ->post(route('admin.questions.store.rlhf'), validRlhfPayload());

        $question = Question::latest('id')->first();

        // Reverse the order (Clarity becomes first, Helpfulness becomes last)
        $payload = validRlhfPayload();
        $reversedCriteria = array_reverse($payload['criteria']);
        foreach ($reversedCriteria as $i => &$c) {
            $c['position'] = $i;
        }
        $payload['criteria'] = $reversedCriteria;

        $this->actingAs($this->admin)
            ->put(route('admin.questions.update.rlhf', $question), $payload)
            ->assertRedirect();

        $question->refresh()->load('rlhfCriteria');
        $names = $question->rlhfCriteria->sortBy('position')->pluck('name')->all();
        expect($names)->toBe(['Clarity', 'Safety', 'Accuracy', 'Helpfulness']);
    });

    test('adds and removes form fields when updating', function () {
        $this->actingAs($this->admin)
            ->post(route('admin.questions.store.rlhf'), validRlhfPayload());

        $question = Question::latest('id')->first();

        // Replace 6 fields with just 2 fields
        $payload = validRlhfPayload([
            'form_fields' => [
                [
                    'stage' => 'post_prompt',
                    'field_key' => 'only_field_one',
                    'label' => 'Only field one',
                    'description' => null,
                    'field_type' => 'radio',
                    'options' => ['A', 'B'],
                    'required' => true,
                    'min_length' => null,
                    'position' => 0,
                ],
                [
                    'stage' => 'post_prompt',
                    'field_key' => 'only_field_two',
                    'label' => 'Only field two',
                    'description' => null,
                    'field_type' => 'text',
                    'options' => null,
                    'required' => false,
                    'min_length' => null,
                    'position' => 1,
                ],
            ],
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.questions.update.rlhf', $question), $payload)
            ->assertRedirect();

        $question->refresh()->load('rlhfFormFields');
        expect($question->rlhfFormFields)->toHaveCount(2);
        $keys = $question->rlhfFormFields->pluck('field_key')->all();
        expect($keys)->toContain('only_field_one', 'only_field_two');
    });

    test('forks new version with force_new_version', function () {
        $this->actingAs($this->admin)
            ->post(route('admin.questions.store.rlhf'), validRlhfPayload(['stem' => 'Original v1']));

        $original = Question::latest('id')->first();

        $this->actingAs($this->admin)
            ->put(route('admin.questions.update.rlhf', $original), validRlhfPayload([
                'stem' => 'Forked v2',
                'force_new_version' => true,
            ]))
            ->assertRedirect();

        $original->refresh();
        expect($original->stem)->toBe('Original v1');

        $newVersion = Question::where('parent_question_id', $original->id)->with(['rlhfConfig', 'rlhfCriteria', 'rlhfFormFields'])->first();
        expect($newVersion)->not->toBeNull()
            ->stem->toBe('Forked v2')
            ->version->toBe(2);
        expect($newVersion->rlhfConfig)->not->toBeNull();
        expect($newVersion->rlhfCriteria)->toHaveCount(4);
        expect($newVersion->rlhfFormFields)->toHaveCount(6);
    });

    test('versioning creates a question.versioned audit entry', function () {
        $this->actingAs($this->admin)
            ->post(route('admin.questions.store.rlhf'), validRlhfPayload());

        $question = Question::latest('id')->first();

        $this->actingAs($this->admin)
            ->put(route('admin.questions.update.rlhf', $question), validRlhfPayload(['force_new_version' => true]));

        $log = AuditLog::where('action', 'question.versioned')->latest('id')->first();
        expect($log)->not->toBeNull()
            ->and($log->changes['parent_id'])->toBe($question->id);
    });
});

describe('controller dispatch', function () {
    test('create page renders for rlhf type', function () {
        $this->actingAs($this->admin)
            ->get(route('admin.questions.create', 'rlhf'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/QuestionBank/Create/Rlhf'));
    });

    test('edit page renders for rlhf question', function () {
        $question = Question::factory()->rlhf()->create();
        $question->rlhfConfig()->create([
            'number_of_turns' => 1,
            'candidate_input_mode' => 'text',
            'model_a' => 'm-a',
            'model_b' => 'm-b',
        ]);
        $question->rlhfCriteria()->create([
            'name' => 'Test',
            'description' => 'd',
            'scale_type' => RlhfScaleType::ThreePointQuality,
            'scale_labels' => ['1', '2', '3'],
            'justification_required_when' => [],
            'position' => 0,
        ]);
        $question->rlhfFormFields()->create([
            'stage' => RlhfFormStage::PostPrompt,
            'field_key' => 'k',
            'label' => 'L',
            'field_type' => RlhfFieldType::Radio,
            'options' => ['a', 'b'],
            'required' => true,
            'position' => 0,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.questions.edit', $question))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/QuestionBank/Edit/Rlhf')
                ->where('question.rlhf_config.number_of_turns', 1)
                ->has('question.rlhf_criteria', 1)
                ->has('question.rlhf_form_fields', 1)
            );
    });
});
