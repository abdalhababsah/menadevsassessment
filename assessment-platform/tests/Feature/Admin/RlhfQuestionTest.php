<?php

use App\Enums\QuestionType;
use App\Enums\RlhfFieldType;
use App\Enums\RlhfFormStage;
use App\Enums\RlhfScaleType;
use App\Models\Question;
use App\Models\RlhfCriterion;
use App\Models\RlhfQuestionConfig;
use App\Models\RlhfQuestionFormField;

test('fully loaded RLHF question has all relationships', function () {
    $question = Question::factory()->rlhf()->create();

    // Create RLHF config
    $config = RlhfQuestionConfig::factory()->withAllForms()->create([
        'question_id' => $question->id,
        'number_of_turns' => 3,
        'model_a' => 'claude-sonnet-4-5-20250514',
        'model_b' => 'gpt-4o',
    ]);

    // Create 4 criteria
    $criteria = RlhfCriterion::factory()
        ->count(4)
        ->sequence(
            ['name' => 'Helpfulness', 'scale_type' => RlhfScaleType::ThreePointQuality, 'position' => 0],
            ['name' => 'Accuracy', 'scale_type' => RlhfScaleType::FivePointCentered, 'position' => 1],
            ['name' => 'Safety', 'scale_type' => RlhfScaleType::ThreePointQuality, 'position' => 2],
            ['name' => 'Verbosity', 'scale_type' => RlhfScaleType::FivePointSatisfaction, 'position' => 3],
        )
        ->create(['question_id' => $question->id]);

    // Create 6 form fields across 3 stages (2 per stage)
    RlhfQuestionFormField::factory()
        ->count(6)
        ->sequence(
            ['stage' => RlhfFormStage::PrePrompt, 'field_key' => 'task_clarity', 'field_type' => RlhfFieldType::Radio, 'position' => 0],
            ['stage' => RlhfFormStage::PrePrompt, 'field_key' => 'initial_impression', 'field_type' => RlhfFieldType::Textarea, 'position' => 1],
            ['stage' => RlhfFormStage::PostPrompt, 'field_key' => 'response_quality', 'field_type' => RlhfFieldType::Radio, 'position' => 0],
            ['stage' => RlhfFormStage::PostPrompt, 'field_key' => 'justification', 'field_type' => RlhfFieldType::Textarea, 'position' => 1],
            ['stage' => RlhfFormStage::PostRewrite, 'field_key' => 'rewrite_improvement', 'field_type' => RlhfFieldType::Dropdown, 'position' => 0],
            ['stage' => RlhfFormStage::PostRewrite, 'field_key' => 'rewrite_notes', 'field_type' => RlhfFieldType::Text, 'position' => 1],
        )
        ->create(['question_id' => $question->id]);

    // Reload with all relationships
    $loaded = Question::with([
        'rlhfConfig',
        'rlhfCriteria',
        'rlhfFormFields',
    ])->find($question->id);

    // Assert question type
    expect($loaded->type)->toBe(QuestionType::Rlhf);

    // Assert config
    expect($loaded->rlhfConfig)
        ->not->toBeNull()
        ->number_of_turns->toBe(3)
        ->model_a->toBe('claude-sonnet-4-5-20250514')
        ->model_b->toBe('gpt-4o')
        ->enable_pre_prompt_form->toBeTrue()
        ->enable_post_prompt_form->toBeTrue()
        ->enable_rewrite_step->toBeTrue()
        ->enable_post_rewrite_form->toBeTrue();

    // Assert criteria
    expect($loaded->rlhfCriteria)->toHaveCount(4);
    expect($loaded->rlhfCriteria[0]->name)->toBe('Helpfulness');
    expect($loaded->rlhfCriteria[0]->scale_type)->toBe(RlhfScaleType::ThreePointQuality);
    expect($loaded->rlhfCriteria[3]->name)->toBe('Verbosity');

    // Assert form fields
    expect($loaded->rlhfFormFields)->toHaveCount(6);

    $prePromptFields = $loaded->rlhfFormFields->where('stage', RlhfFormStage::PrePrompt);
    $postPromptFields = $loaded->rlhfFormFields->where('stage', RlhfFormStage::PostPrompt);
    $postRewriteFields = $loaded->rlhfFormFields->where('stage', RlhfFormStage::PostRewrite);

    expect($prePromptFields)->toHaveCount(2);
    expect($postPromptFields)->toHaveCount(2);
    expect($postRewriteFields)->toHaveCount(2);
});

test('rlhf config casts generation_params to array', function () {
    $config = RlhfQuestionConfig::factory()->create([
        'generation_params' => ['temperature' => 0.9, 'max_tokens' => 4096],
    ]);

    $config->refresh();

    expect($config->generation_params)
        ->toBeArray()
        ->toHaveKey('temperature')
        ->and($config->generation_params['temperature'])->toBe(0.9);
});

test('rlhf config criteria and formFields relationships via config', function () {
    $question = Question::factory()->rlhf()->create();

    $config = RlhfQuestionConfig::factory()->create(['question_id' => $question->id]);
    RlhfCriterion::factory()->count(2)->create(['question_id' => $question->id]);
    RlhfQuestionFormField::factory()->count(3)->create(['question_id' => $question->id]);

    $config->refresh();

    expect($config->criteria)->toHaveCount(2);
    expect($config->formFields)->toHaveCount(3);
});

test('rlhf criterion casts scale_type to enum', function () {
    $criterion = RlhfCriterion::factory()->threePointQuality()->create();

    $criterion->refresh();

    expect($criterion->scale_type)->toBe(RlhfScaleType::ThreePointQuality)
        ->and($criterion->scale_labels)->toBeArray()
        ->and($criterion->justification_required_when)->toBeArray();
});

test('rlhf form field casts stage and field_type to enums', function () {
    $field = RlhfQuestionFormField::factory()->prePrompt()->radio()->create();

    $field->refresh();

    expect($field->stage)->toBe(RlhfFormStage::PrePrompt)
        ->and($field->field_type)->toBe(RlhfFieldType::Radio)
        ->and($field->options)->toBeArray()
        ->and($field->required)->toBeTrue();
});
