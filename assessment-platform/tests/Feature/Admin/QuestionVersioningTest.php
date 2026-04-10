<?php

use App\Enums\QuestionDifficulty;
use App\Enums\RlhfFormStage;
use App\Enums\RlhfScaleType;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizSection;
use App\Models\Tag;
use App\Models\User;
use App\Services\QuestionBank\QuestionVersioningService;
use Database\Seeders\PermissionSeeder;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->service = app(QuestionVersioningService::class);
});

describe('forkNewVersion copies all relations', function () {
    test('copies tags, options, and media for select question', function () {
        $tag1 = Tag::factory()->create();
        $tag2 = Tag::factory()->create();

        $original = Question::factory()->singleSelect()->medium()->create([
            'stem' => 'Original stem',
            'points' => 2.5,
        ]);
        $original->tags()->sync([$tag1->id, $tag2->id]);
        $original->options()->create(['content' => 'A', 'is_correct' => false, 'position' => 0]);
        $original->options()->create(['content' => 'B', 'is_correct' => true, 'position' => 1]);
        $original->media()->create(['media_type' => 'image', 'url' => 'https://example.com/image.png', 'position' => 0]);

        $newVersion = $this->service->forkNewVersion($original, []);

        expect($newVersion->id)->not->toBe($original->id)
            ->and($newVersion->parent_question_id)->toBe($original->id)
            ->and($newVersion->version)->toBe(2)
            ->and($newVersion->stem)->toBe('Original stem')
            ->and($newVersion->difficulty)->toBe(QuestionDifficulty::Medium);

        expect($newVersion->tags)->toHaveCount(2);
        expect($newVersion->tags->pluck('id')->all())->toEqualCanonicalizing([$tag1->id, $tag2->id]);

        expect($newVersion->options)->toHaveCount(2);
        expect($newVersion->options->where('is_correct', true)->first()->content)->toBe('B');

        expect($newVersion->media)->toHaveCount(1);
        expect($newVersion->media->first()->url)->toBe('https://example.com/image.png');
    });

    test('copies coding config and test cases', function () {
        $original = Question::factory()->coding()->create();
        $original->codingConfig()->create([
            'allowed_languages' => ['python', 'javascript'],
            'starter_code' => ['python' => 'def solve():'],
            'time_limit_ms' => 5000,
            'memory_limit_mb' => 128,
        ]);
        $original->testCases()->create(['input' => '1', 'expected_output' => '1', 'is_hidden' => true, 'weight' => 1.5]);
        $original->testCases()->create(['input' => '2', 'expected_output' => '4', 'is_hidden' => false, 'weight' => 2.0]);

        $newVersion = $this->service->forkNewVersion($original, []);

        expect($newVersion->codingConfig)->not->toBeNull()
            ->time_limit_ms->toBe(5000)
            ->memory_limit_mb->toBe(128);
        expect($newVersion->codingConfig->allowed_languages)->toBe(['python', 'javascript']);

        expect($newVersion->testCases)->toHaveCount(2);
        expect((float) $newVersion->testCases->where('is_hidden', false)->first()->weight)->toBe(2.0);
    });

    test('copies rlhf config, criteria, and form fields', function () {
        $original = Question::factory()->rlhf()->create();
        $original->rlhfConfig()->create([
            'number_of_turns' => 3,
            'candidate_input_mode' => 'text',
            'model_a' => 'claude',
            'model_b' => 'gpt',
            'enable_post_prompt_form' => true,
        ]);
        $original->rlhfCriteria()->create([
            'name' => 'Helpfulness',
            'description' => 'd',
            'scale_type' => RlhfScaleType::ThreePointQuality,
            'scale_labels' => ['1', '2', '3'],
            'justification_required_when' => [1],
            'position' => 0,
        ]);
        $original->rlhfFormFields()->create([
            'stage' => RlhfFormStage::PostPrompt,
            'field_key' => 'k',
            'label' => 'L',
            'field_type' => 'radio',
            'options' => ['a', 'b'],
            'required' => true,
            'position' => 0,
        ]);

        $newVersion = $this->service->forkNewVersion($original, []);

        expect($newVersion->rlhfConfig)->not->toBeNull()
            ->number_of_turns->toBe(3)
            ->model_a->toBe('claude');
        expect($newVersion->rlhfCriteria)->toHaveCount(1);
        expect($newVersion->rlhfCriteria->first()->name)->toBe('Helpfulness');
        expect($newVersion->rlhfFormFields)->toHaveCount(1);
        expect($newVersion->rlhfFormFields->first()->field_key)->toBe('k');
    });

    test('applies scalar changes on top of copied data', function () {
        $original = Question::factory()->singleSelect()->create([
            'stem' => 'Old stem',
            'points' => 1,
        ]);
        $original->options()->create(['content' => 'A', 'is_correct' => true, 'position' => 0]);

        $newVersion = $this->service->forkNewVersion($original, [
            'stem' => 'New stem',
            'points' => 5.0,
            'difficulty' => QuestionDifficulty::Hard,
        ]);

        expect($newVersion->stem)->toBe('New stem')
            ->and((float) $newVersion->points)->toBe(5.0)
            ->and($newVersion->difficulty)->toBe(QuestionDifficulty::Hard);
        // Options were not in changes, so they should be copied
        expect($newVersion->options)->toHaveCount(1);
    });

    test('replaces options when options change is provided', function () {
        $original = Question::factory()->singleSelect()->create();
        $original->options()->create(['content' => 'OldA', 'is_correct' => true, 'position' => 0]);
        $original->options()->create(['content' => 'OldB', 'is_correct' => false, 'position' => 1]);

        $newVersion = $this->service->forkNewVersion($original, [
            'options' => [
                ['content' => 'NewA', 'content_type' => 'text', 'is_correct' => true, 'position' => 0],
                ['content' => 'NewB', 'content_type' => 'text', 'is_correct' => false, 'position' => 1],
                ['content' => 'NewC', 'content_type' => 'text', 'is_correct' => false, 'position' => 2],
            ],
        ]);

        expect($newVersion->options)->toHaveCount(3);
        expect($newVersion->options->pluck('content')->sort()->values()->all())
            ->toBe(['NewA', 'NewB', 'NewC']);
    });
});

describe('parent/version chain queries', function () {
    test('isUsedInQuizzes returns false for unused question', function () {
        $question = Question::factory()->singleSelect()->create();
        expect($this->service->isUsedInQuizzes($question))->toBeFalse();
    });

    test('isUsedInQuizzes returns true when attached to a quiz section', function () {
        $question = Question::factory()->singleSelect()->create();
        $section = QuizSection::factory()->create();
        $section->questions()->attach($question->id, ['question_version' => 1, 'position' => 0]);

        expect($this->service->isUsedInQuizzes($question))->toBeTrue();
    });

    test('usagesIn returns the section/quiz pivot rows', function () {
        $question = Question::factory()->singleSelect()->create();
        $quiz = Quiz::factory()->create(['title' => 'Quiz One']);
        $section = QuizSection::factory()->create(['quiz_id' => $quiz->id]);
        $section->questions()->attach($question->id, ['question_version' => 1, 'position' => 0]);

        $usages = $this->service->usagesIn($question);

        expect($usages)->toHaveCount(1);
        expect($usages->first()->section->quiz->title)->toBe('Quiz One');
    });

    test('latestVersionOf walks the chain', function () {
        $v1 = Question::factory()->singleSelect()->create(['version' => 1]);
        $v2 = $this->service->forkNewVersion($v1, ['stem' => 'v2']);
        $v3 = $this->service->forkNewVersion($v2, ['stem' => 'v3']);

        expect($this->service->latestVersionOf($v1)->id)->toBe($v3->id);
        expect($this->service->latestVersionOf($v2)->id)->toBe($v3->id);
        expect($this->service->latestVersionOf($v3)->id)->toBe($v3->id);
    });
});

describe('multiple forks form a tree', function () {
    test('forking the same parent twice creates sibling versions', function () {
        $root = Question::factory()->singleSelect()->create(['stem' => 'Root']);
        $branchA = $this->service->forkNewVersion($root, ['stem' => 'Branch A']);
        $branchB = $this->service->forkNewVersion($root, ['stem' => 'Branch B']);

        expect($branchA->parent_question_id)->toBe($root->id);
        expect($branchB->parent_question_id)->toBe($root->id);
        expect($branchA->id)->not->toBe($branchB->id);

        // Both branches are version 2 (incremented from root v1)
        expect($branchA->version)->toBe(2);
        expect($branchB->version)->toBe(2);

        $root->load('versions');
        expect($root->versions)->toHaveCount(2);
    });

    test('deep tree returns latest by version then id', function () {
        $root = Question::factory()->singleSelect()->create(['version' => 1]);
        $a = $this->service->forkNewVersion($root, ['stem' => 'A']); // v2
        $aa = $this->service->forkNewVersion($a, ['stem' => 'AA']); // v3
        $b = $this->service->forkNewVersion($root, ['stem' => 'B']); // v2

        $latest = $this->service->latestVersionOf($root);

        // AA is v3, the deepest descendant
        expect($latest->id)->toBe($aa->id);
        expect($latest->version)->toBe(3);
    });
});

describe('update action falls back to fork when in use', function () {
    test('update-in-place works when question is not used', function () {
        $admin = User::factory()->superAdmin()->create();
        $question = Question::factory()->singleSelect()->create(['stem' => 'Before']);
        $question->options()->create(['content' => 'A', 'is_correct' => true, 'position' => 0]);
        $question->options()->create(['content' => 'B', 'is_correct' => false, 'position' => 1]);

        $this->actingAs($admin)
            ->put(route('admin.questions.update.single-select', $question), [
                'stem' => 'After',
                'difficulty' => 'easy',
                'points' => 1,
                'options' => [
                    ['content' => 'X', 'content_type' => 'text', 'is_correct' => true, 'position' => 0],
                    ['content' => 'Y', 'content_type' => 'text', 'is_correct' => false, 'position' => 1],
                ],
            ])
            ->assertRedirect();

        $question->refresh();
        expect($question->stem)->toBe('After')
            ->and($question->version)->toBe(1);

        // No new version created
        expect(Question::where('parent_question_id', $question->id)->count())->toBe(0);
    });

    test('auto-forks when question is used in a quiz', function () {
        $admin = User::factory()->superAdmin()->create();
        $question = Question::factory()->singleSelect()->create(['stem' => 'In use']);
        $question->options()->create(['content' => 'A', 'is_correct' => true, 'position' => 0]);
        $question->options()->create(['content' => 'B', 'is_correct' => false, 'position' => 1]);

        $section = QuizSection::factory()->create();
        $section->questions()->attach($question->id, ['question_version' => 1, 'position' => 0]);

        $this->actingAs($admin)
            ->put(route('admin.questions.update.single-select', $question), [
                'stem' => 'Edited copy',
                'difficulty' => 'medium',
                'points' => 1,
                'options' => [
                    ['content' => 'A', 'content_type' => 'text', 'is_correct' => true, 'position' => 0],
                    ['content' => 'B', 'content_type' => 'text', 'is_correct' => false, 'position' => 1],
                ],
            ])
            ->assertRedirect();

        $question->refresh();
        // Original is untouched
        expect($question->stem)->toBe('In use');

        // A new fork exists
        $fork = Question::where('parent_question_id', $question->id)->first();
        expect($fork)->not->toBeNull()
            ->stem->toBe('Edited copy')
            ->version->toBe(2);
    });

    test('force_in_place updates the question even when in use', function () {
        $admin = User::factory()->superAdmin()->create();
        $question = Question::factory()->singleSelect()->create(['stem' => 'Original']);
        $question->options()->create(['content' => 'A', 'is_correct' => true, 'position' => 0]);
        $question->options()->create(['content' => 'B', 'is_correct' => false, 'position' => 1]);

        $section = QuizSection::factory()->create();
        $section->questions()->attach($question->id, ['question_version' => 1, 'position' => 0]);

        $this->actingAs($admin)
            ->put(route('admin.questions.update.single-select', $question), [
                'stem' => 'Forced update',
                'difficulty' => 'medium',
                'points' => 1,
                'force_in_place' => true,
                'options' => [
                    ['content' => 'A', 'content_type' => 'text', 'is_correct' => true, 'position' => 0],
                    ['content' => 'B', 'content_type' => 'text', 'is_correct' => false, 'position' => 1],
                ],
            ])
            ->assertRedirect();

        $question->refresh();
        expect($question->stem)->toBe('Forced update');
        expect(Question::where('parent_question_id', $question->id)->count())->toBe(0);
    });

    test('force_new_version always forks even when not in use', function () {
        $admin = User::factory()->superAdmin()->create();
        $question = Question::factory()->singleSelect()->create(['stem' => 'Original']);
        $question->options()->create(['content' => 'A', 'is_correct' => true, 'position' => 0]);
        $question->options()->create(['content' => 'B', 'is_correct' => false, 'position' => 1]);

        $this->actingAs($admin)
            ->put(route('admin.questions.update.single-select', $question), [
                'stem' => 'Forked',
                'difficulty' => 'medium',
                'points' => 1,
                'force_new_version' => true,
                'options' => [
                    ['content' => 'A', 'content_type' => 'text', 'is_correct' => true, 'position' => 0],
                    ['content' => 'B', 'content_type' => 'text', 'is_correct' => false, 'position' => 1],
                ],
            ])
            ->assertRedirect();

        $question->refresh();
        expect($question->stem)->toBe('Original');

        $fork = Question::where('parent_question_id', $question->id)->first();
        expect($fork)->not->toBeNull()
            ->stem->toBe('Forked');
    });
});
