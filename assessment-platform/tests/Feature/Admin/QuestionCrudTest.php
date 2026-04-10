<?php

use App\Enums\QuestionDifficulty;
use App\Enums\QuestionType;
use App\Models\AuditLog;
use App\Models\Question;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\PermissionSeeder;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->admin = User::factory()->superAdmin()->create();
});

describe('create routes', function () {
    test('renders create page for each valid type', function () {
        foreach (['single_select', 'multi_select', 'coding'] as $type) {
            $this->actingAs($this->admin)
                ->get(route('admin.questions.create', $type))
                ->assertOk();
        }
    });

    test('returns 404 for invalid type', function () {
        $this->actingAs($this->admin)
            ->get(route('admin.questions.create', 'invalid'))
            ->assertNotFound();
    });
});

describe('single-select questions', function () {
    test('creates a single-select question with options and tags', function () {
        $tag = Tag::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.questions.store.single-select'), [
                'stem' => 'What is 2+2?',
                'instructions' => 'Choose the right answer',
                'difficulty' => 'easy',
                'points' => 1,
                'tags' => [$tag->id],
                'options' => [
                    ['content' => '3', 'content_type' => 'text', 'is_correct' => false, 'position' => 0],
                    ['content' => '4', 'content_type' => 'text', 'is_correct' => true, 'position' => 1],
                    ['content' => '5', 'content_type' => 'text', 'is_correct' => false, 'position' => 2],
                ],
            ])
            ->assertRedirect(route('admin.questions.index'));

        $question = Question::latest('id')->first();
        expect($question)
            ->type->toBe(QuestionType::SingleSelect)
            ->stem->toBe('What is 2+2?')
            ->difficulty->toBe(QuestionDifficulty::Easy);

        expect($question->options)->toHaveCount(3);
        expect($question->options->where('is_correct', true)->count())->toBe(1);
        expect($question->tags)->toHaveCount(1);
    });

    test('rejects single-select with no correct option', function () {
        $this->actingAs($this->admin)
            ->post(route('admin.questions.store.single-select'), [
                'stem' => 'Pick one',
                'difficulty' => 'easy',
                'points' => 1,
                'options' => [
                    ['content' => 'A', 'content_type' => 'text', 'is_correct' => false, 'position' => 0],
                    ['content' => 'B', 'content_type' => 'text', 'is_correct' => false, 'position' => 1],
                ],
            ])
            ->assertSessionHasErrors('options');
    });

    test('rejects single-select with multiple correct options', function () {
        $this->actingAs($this->admin)
            ->post(route('admin.questions.store.single-select'), [
                'stem' => 'Pick one',
                'difficulty' => 'easy',
                'points' => 1,
                'options' => [
                    ['content' => 'A', 'content_type' => 'text', 'is_correct' => true, 'position' => 0],
                    ['content' => 'B', 'content_type' => 'text', 'is_correct' => true, 'position' => 1],
                ],
            ])
            ->assertSessionHasErrors('options');
    });

    test('rejects single-select with fewer than 2 options', function () {
        $this->actingAs($this->admin)
            ->post(route('admin.questions.store.single-select'), [
                'stem' => 'Pick one',
                'difficulty' => 'easy',
                'points' => 1,
                'options' => [
                    ['content' => 'Only one', 'content_type' => 'text', 'is_correct' => true, 'position' => 0],
                ],
            ])
            ->assertSessionHasErrors('options');
    });

    test('updates a single-select question in place', function () {
        $question = Question::factory()->singleSelect()->create(['stem' => 'Old stem']);
        $question->options()->create(['content' => 'A', 'is_correct' => true, 'position' => 0]);
        $question->options()->create(['content' => 'B', 'is_correct' => false, 'position' => 1]);

        $this->actingAs($this->admin)
            ->put(route('admin.questions.update.single-select', $question), [
                'stem' => 'New stem',
                'difficulty' => 'medium',
                'points' => 2,
                'options' => [
                    ['content' => 'X', 'content_type' => 'text', 'is_correct' => true, 'position' => 0],
                    ['content' => 'Y', 'content_type' => 'text', 'is_correct' => false, 'position' => 1],
                ],
            ])
            ->assertRedirect(route('admin.questions.index'));

        $question->refresh();
        expect($question->stem)->toBe('New stem')
            ->and($question->options->pluck('content')->sort()->values()->all())->toBe(['X', 'Y'])
            ->and($question->version)->toBe(1);
    });

    test('forks a new version when force_new_version is true', function () {
        $original = Question::factory()->singleSelect()->create(['stem' => 'Original', 'version' => 1]);
        $original->options()->create(['content' => 'A', 'is_correct' => true, 'position' => 0]);

        $this->actingAs($this->admin)
            ->put(route('admin.questions.update.single-select', $original), [
                'stem' => 'Forked',
                'difficulty' => 'hard',
                'points' => 5,
                'force_new_version' => true,
                'options' => [
                    ['content' => 'X', 'content_type' => 'text', 'is_correct' => true, 'position' => 0],
                    ['content' => 'Y', 'content_type' => 'text', 'is_correct' => false, 'position' => 1],
                ],
            ])
            ->assertRedirect(route('admin.questions.index'));

        $original->refresh();
        expect($original->stem)->toBe('Original');

        $newVersion = Question::where('parent_question_id', $original->id)->first();
        expect($newVersion)
            ->not->toBeNull()
            ->stem->toBe('Forked')
            ->version->toBe(2);
    });
});

describe('multi-select questions', function () {
    test('creates a multi-select question with multiple correct options', function () {
        $this->actingAs($this->admin)
            ->post(route('admin.questions.store.multi-select'), [
                'stem' => 'Select all that apply',
                'difficulty' => 'medium',
                'points' => 2,
                'options' => [
                    ['content' => 'A', 'content_type' => 'text', 'is_correct' => true, 'position' => 0],
                    ['content' => 'B', 'content_type' => 'text', 'is_correct' => true, 'position' => 1],
                    ['content' => 'C', 'content_type' => 'text', 'is_correct' => false, 'position' => 2],
                ],
            ])
            ->assertRedirect();

        $question = Question::latest('id')->first();
        expect($question->type)->toBe(QuestionType::MultiSelect);
        expect($question->options->where('is_correct', true)->count())->toBe(2);
    });

    test('rejects multi-select with zero correct options', function () {
        $this->actingAs($this->admin)
            ->post(route('admin.questions.store.multi-select'), [
                'stem' => 'Select',
                'difficulty' => 'medium',
                'points' => 1,
                'options' => [
                    ['content' => 'A', 'content_type' => 'text', 'is_correct' => false, 'position' => 0],
                    ['content' => 'B', 'content_type' => 'text', 'is_correct' => false, 'position' => 1],
                ],
            ])
            ->assertSessionHasErrors('options');
    });

    test('updates and version-forks multi-select questions', function () {
        $question = Question::factory()->multiSelect()->create();
        $question->options()->create(['content' => 'A', 'is_correct' => true, 'position' => 0]);
        $question->options()->create(['content' => 'B', 'is_correct' => true, 'position' => 1]);

        $this->actingAs($this->admin)
            ->put(route('admin.questions.update.multi-select', $question), [
                'stem' => 'Updated multi',
                'difficulty' => 'hard',
                'points' => 3,
                'force_new_version' => true,
                'options' => [
                    ['content' => 'A', 'content_type' => 'text', 'is_correct' => true, 'position' => 0],
                    ['content' => 'B', 'content_type' => 'text', 'is_correct' => true, 'position' => 1],
                    ['content' => 'C', 'content_type' => 'text', 'is_correct' => false, 'position' => 2],
                ],
            ])
            ->assertRedirect();

        $newVersion = Question::where('parent_question_id', $question->id)->first();
        expect($newVersion)->not->toBeNull()->and($newVersion->options)->toHaveCount(3);
    });
});

describe('coding questions', function () {
    test('creates a coding question with config and test cases', function () {
        $this->actingAs($this->admin)
            ->post(route('admin.questions.store.coding'), [
                'stem' => 'Implement quicksort',
                'difficulty' => 'hard',
                'points' => 10,
                'allowed_languages' => ['python', 'javascript'],
                'starter_code' => ['python' => 'def sort(arr):'],
                'time_limit_ms' => 5000,
                'memory_limit_mb' => 128,
                'test_cases' => [
                    ['input' => '[3,1,2]', 'expected_output' => '[1,2,3]', 'is_hidden' => false, 'weight' => 1.0],
                    ['input' => '[5,4,3,2,1]', 'expected_output' => '[1,2,3,4,5]', 'is_hidden' => true, 'weight' => 2.0],
                ],
            ])
            ->assertRedirect();

        $question = Question::latest('id')->first();
        expect($question->type)->toBe(QuestionType::Coding);
        expect($question->codingConfig)
            ->not->toBeNull()
            ->time_limit_ms->toBe(5000)
            ->memory_limit_mb->toBe(128);
        expect($question->codingConfig->allowed_languages)->toBe(['python', 'javascript']);
        expect($question->testCases)->toHaveCount(2);
    });

    test('rejects coding question with no test cases', function () {
        $this->actingAs($this->admin)
            ->post(route('admin.questions.store.coding'), [
                'stem' => 'Test',
                'difficulty' => 'easy',
                'points' => 1,
                'allowed_languages' => ['python'],
                'time_limit_ms' => 1000,
                'memory_limit_mb' => 64,
                'test_cases' => [],
            ])
            ->assertSessionHasErrors('test_cases');
    });

    test('rejects coding question with no allowed languages', function () {
        $this->actingAs($this->admin)
            ->post(route('admin.questions.store.coding'), [
                'stem' => 'Test',
                'difficulty' => 'easy',
                'points' => 1,
                'allowed_languages' => [],
                'time_limit_ms' => 1000,
                'memory_limit_mb' => 64,
                'test_cases' => [
                    ['input' => 'a', 'expected_output' => 'b', 'is_hidden' => true, 'weight' => 1],
                ],
            ])
            ->assertSessionHasErrors('allowed_languages');
    });

    test('updates coding question in place', function () {
        $question = Question::factory()->coding()->create();
        $question->codingConfig()->create([
            'allowed_languages' => ['python'],
            'time_limit_ms' => 1000,
            'memory_limit_mb' => 64,
        ]);
        $question->testCases()->create(['input' => '1', 'expected_output' => '1', 'is_hidden' => true, 'weight' => 1]);

        $this->actingAs($this->admin)
            ->put(route('admin.questions.update.coding', $question), [
                'stem' => 'Updated',
                'difficulty' => 'hard',
                'points' => 10,
                'allowed_languages' => ['python', 'go'],
                'time_limit_ms' => 3000,
                'memory_limit_mb' => 256,
                'test_cases' => [
                    ['input' => '2', 'expected_output' => '2', 'is_hidden' => false, 'weight' => 1.5],
                ],
            ])
            ->assertRedirect();

        $question->refresh();
        expect($question->stem)->toBe('Updated')
            ->and($question->codingConfig->time_limit_ms)->toBe(3000)
            ->and($question->codingConfig->allowed_languages)->toBe(['python', 'go'])
            ->and($question->testCases)->toHaveCount(1);
    });

    test('forks new version of coding question', function () {
        $original = Question::factory()->coding()->create(['stem' => 'Original']);
        $original->codingConfig()->create([
            'allowed_languages' => ['python'],
            'time_limit_ms' => 1000,
            'memory_limit_mb' => 64,
        ]);
        $original->testCases()->create(['input' => 'a', 'expected_output' => 'a', 'is_hidden' => true, 'weight' => 1]);

        $this->actingAs($this->admin)
            ->put(route('admin.questions.update.coding', $original), [
                'stem' => 'Forked v2',
                'difficulty' => 'medium',
                'points' => 5,
                'force_new_version' => true,
                'allowed_languages' => ['python', 'rust'],
                'time_limit_ms' => 2000,
                'memory_limit_mb' => 128,
                'test_cases' => [
                    ['input' => 'b', 'expected_output' => 'b', 'is_hidden' => false, 'weight' => 1],
                ],
            ])
            ->assertRedirect();

        $original->refresh();
        expect($original->stem)->toBe('Original');

        $newVersion = Question::where('parent_question_id', $original->id)->with('codingConfig', 'testCases')->first();
        expect($newVersion)
            ->not->toBeNull()
            ->stem->toBe('Forked v2')
            ->version->toBe(2);
        expect($newVersion->codingConfig->allowed_languages)->toBe(['python', 'rust']);
        expect($newVersion->testCases)->toHaveCount(1);
    });
});

describe('audit log', function () {
    test('creating a question creates audit log entry', function () {
        $this->actingAs($this->admin)
            ->post(route('admin.questions.store.single-select'), [
                'stem' => 'Audit test',
                'difficulty' => 'easy',
                'points' => 1,
                'options' => [
                    ['content' => 'A', 'content_type' => 'text', 'is_correct' => true, 'position' => 0],
                    ['content' => 'B', 'content_type' => 'text', 'is_correct' => false, 'position' => 1],
                ],
            ]);

        $log = AuditLog::where('action', 'question.created')->latest('id')->first();
        expect($log)->not->toBeNull()
            ->and($log->changes['type'])->toBe('single_select');
    });

    test('versioning a question creates a question.versioned audit entry', function () {
        $question = Question::factory()->singleSelect()->create();
        $question->options()->create(['content' => 'A', 'is_correct' => true, 'position' => 0]);

        $this->actingAs($this->admin)
            ->put(route('admin.questions.update.single-select', $question), [
                'stem' => 'Versioned',
                'difficulty' => 'easy',
                'points' => 1,
                'force_new_version' => true,
                'options' => [
                    ['content' => 'A', 'content_type' => 'text', 'is_correct' => true, 'position' => 0],
                    ['content' => 'B', 'content_type' => 'text', 'is_correct' => false, 'position' => 1],
                ],
            ]);

        $log = AuditLog::where('action', 'question.versioned')->latest('id')->first();
        expect($log)->not->toBeNull()
            ->and($log->changes['parent_id'])->toBe($question->id);
    });
});

describe('authorization', function () {
    test('user without questionbank.create cannot create', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.questions.store.single-select'), [])
            ->assertForbidden();
    });
});
