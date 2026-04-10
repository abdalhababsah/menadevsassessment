<?php

use App\Models\AuditLog;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizSection;
use App\Models\QuizSectionQuestion;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->admin = User::factory()->superAdmin()->create();
});

describe('section CRUD', function () {
    test('creates a section with auto-incremented position', function () {
        $quiz = Quiz::factory()->create();
        QuizSection::factory()->create(['quiz_id' => $quiz->id, 'position' => 0]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.quizzes.sections.store', $quiz), [
                'title' => 'New Section',
                'description' => 'Section description',
                'time_limit_seconds' => 600,
            ]);

        $response->assertSessionHasNoErrors()->assertRedirect();

        $section = $quiz->sections()->where('title', 'New Section')->first();
        expect($section)
            ->not->toBeNull()
            ->title->toBe('New Section')
            ->position->toBe(1)
            ->time_limit_seconds->toBe(600);
    });

    test('updates a section', function () {
        $quiz = Quiz::factory()->create();
        $section = QuizSection::factory()->create(['quiz_id' => $quiz->id, 'title' => 'Old']);

        $this->actingAs($this->admin)
            ->put(route('admin.quizzes.sections.update', [$quiz, $section]), [
                'title' => 'Updated',
                'description' => null,
                'time_limit_seconds' => null,
            ])
            ->assertRedirect();

        expect($section->fresh()->title)->toBe('Updated');
    });

    test('deletes a section', function () {
        $quiz = Quiz::factory()->create();
        $section = QuizSection::factory()->create(['quiz_id' => $quiz->id]);

        $this->actingAs($this->admin)
            ->delete(route('admin.quizzes.sections.destroy', [$quiz, $section]))
            ->assertRedirect();

        expect(QuizSection::find($section->id))->toBeNull();
    });

    test('section update validates the section belongs to the quiz', function () {
        $quizA = Quiz::factory()->create();
        $quizB = Quiz::factory()->create();
        $sectionOfB = QuizSection::factory()->create(['quiz_id' => $quizB->id]);

        $this->actingAs($this->admin)
            ->put(route('admin.quizzes.sections.update', [$quizA, $sectionOfB]), [
                'title' => 'Hijack',
                'description' => null,
                'time_limit_seconds' => null,
            ])
            ->assertNotFound();
    });
});

describe('section reorder', function () {
    test('reorders sections', function () {
        $quiz = Quiz::factory()->create();
        $s1 = QuizSection::factory()->create(['quiz_id' => $quiz->id, 'title' => 'A', 'position' => 0]);
        $s2 = QuizSection::factory()->create(['quiz_id' => $quiz->id, 'title' => 'B', 'position' => 1]);
        $s3 = QuizSection::factory()->create(['quiz_id' => $quiz->id, 'title' => 'C', 'position' => 2]);

        $this->actingAs($this->admin)
            ->post(route('admin.quizzes.sections.reorder', $quiz), [
                'section_ids' => [$s3->id, $s1->id, $s2->id],
            ])
            ->assertRedirect();

        expect($s3->fresh()->position)->toBe(0)
            ->and($s1->fresh()->position)->toBe(1)
            ->and($s2->fresh()->position)->toBe(2);
    });
});

describe('attach question (snapshots version)', function () {
    test('snapshots question_version into the pivot', function () {
        $quiz = Quiz::factory()->create();
        $section = QuizSection::factory()->create(['quiz_id' => $quiz->id]);
        $question = Question::factory()->singleSelect()->create(['version' => 5]);

        $this->actingAs($this->admin)
            ->post(route('admin.quizzes.sections.questions.attach', [$quiz, $section]), [
                'question_id' => $question->id,
                'points_override' => 7.5,
                'time_limit_override_seconds' => 90,
            ])
            ->assertRedirect();

        $pivot = QuizSectionQuestion::where('quiz_section_id', $section->id)->first();
        expect($pivot)
            ->not->toBeNull()
            ->question_id->toBe($question->id)
            ->question_version->toBe(5)
            ->position->toBe(0);
        expect((float) $pivot->points_override)->toBe(7.5);
        expect($pivot->time_limit_override_seconds)->toBe(90);
    });

    test('uses next position when attaching multiple', function () {
        $quiz = Quiz::factory()->create();
        $section = QuizSection::factory()->create(['quiz_id' => $quiz->id]);
        $q1 = Question::factory()->singleSelect()->create();
        $q2 = Question::factory()->singleSelect()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.quizzes.sections.questions.attach', [$quiz, $section]), [
                'question_id' => $q1->id,
            ]);
        $this->actingAs($this->admin)
            ->post(route('admin.quizzes.sections.questions.attach', [$quiz, $section]), [
                'question_id' => $q2->id,
            ]);

        $pivots = QuizSectionQuestion::where('quiz_section_id', $section->id)->orderBy('position')->get();
        expect($pivots)->toHaveCount(2);
        expect($pivots[0]->position)->toBe(0);
        expect($pivots[1]->position)->toBe(1);
    });
});

describe('detach question', function () {
    test('removes the pivot row', function () {
        $quiz = Quiz::factory()->create();
        $section = QuizSection::factory()->create(['quiz_id' => $quiz->id]);
        $question = Question::factory()->singleSelect()->create();
        $pivot = $section->sectionQuestions()->create([
            'question_id' => $question->id,
            'question_version' => 1,
            'position' => 0,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('admin.quizzes.sections.questions.detach', [$quiz, $section, $pivot]))
            ->assertRedirect();

        expect(QuizSectionQuestion::find($pivot->id))->toBeNull();
    });
});

describe('reorder questions within a section', function () {
    test('reorders pivots by id list', function () {
        $quiz = Quiz::factory()->create();
        $section = QuizSection::factory()->create(['quiz_id' => $quiz->id]);
        $q1 = Question::factory()->singleSelect()->create();
        $q2 = Question::factory()->singleSelect()->create();
        $q3 = Question::factory()->singleSelect()->create();

        $p1 = $section->sectionQuestions()->create(['question_id' => $q1->id, 'question_version' => 1, 'position' => 0]);
        $p2 = $section->sectionQuestions()->create(['question_id' => $q2->id, 'question_version' => 1, 'position' => 1]);
        $p3 = $section->sectionQuestions()->create(['question_id' => $q3->id, 'question_version' => 1, 'position' => 2]);

        $this->actingAs($this->admin)
            ->post(route('admin.quizzes.sections.questions.reorder', [$quiz, $section]), [
                'section_question_ids' => [$p3->id, $p1->id, $p2->id],
            ])
            ->assertRedirect();

        expect($p3->fresh()->position)->toBe(0)
            ->and($p1->fresh()->position)->toBe(1)
            ->and($p2->fresh()->position)->toBe(2);
    });
});

describe('update pivot overrides', function () {
    test('updates points and time overrides', function () {
        $quiz = Quiz::factory()->create();
        $section = QuizSection::factory()->create(['quiz_id' => $quiz->id]);
        $question = Question::factory()->singleSelect()->create();
        $pivot = $section->sectionQuestions()->create([
            'question_id' => $question->id,
            'question_version' => 1,
            'position' => 0,
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.quizzes.sections.questions.update', [$quiz, $section, $pivot]), [
                'points_override' => 12.5,
                'time_limit_override_seconds' => 180,
            ])
            ->assertRedirect();

        $pivot->refresh();
        expect((float) $pivot->points_override)->toBe(12.5)
            ->and($pivot->time_limit_override_seconds)->toBe(180);
    });
});

describe('inline question creation', function () {
    test('creates a single-select question and attaches it', function () {
        $quiz = Quiz::factory()->create();
        $section = QuizSection::factory()->create(['quiz_id' => $quiz->id]);

        $this->actingAs($this->admin)
            ->post(route('admin.quizzes.sections.questions.inline.single-select', [$quiz, $section]), [
                'stem' => 'Inline question stem',
                'difficulty' => 'easy',
                'points' => 1,
                'options' => [
                    ['content' => 'A', 'content_type' => 'text', 'is_correct' => true, 'position' => 0],
                    ['content' => 'B', 'content_type' => 'text', 'is_correct' => false, 'position' => 1],
                ],
            ])
            ->assertRedirect();

        $question = Question::where('stem', 'Inline question stem')->first();
        expect($question)->not->toBeNull();

        $pivot = QuizSectionQuestion::where('quiz_section_id', $section->id)
            ->where('question_id', $question->id)
            ->first();
        expect($pivot)->not->toBeNull();
    });

    test('creates a coding question and attaches it', function () {
        $quiz = Quiz::factory()->create();
        $section = QuizSection::factory()->create(['quiz_id' => $quiz->id]);

        $this->actingAs($this->admin)
            ->post(route('admin.quizzes.sections.questions.inline.coding', [$quiz, $section]), [
                'stem' => 'Inline coding stem',
                'difficulty' => 'medium',
                'points' => 5,
                'allowed_languages' => ['python'],
                'time_limit_ms' => 1000,
                'memory_limit_mb' => 64,
                'test_cases' => [
                    ['input' => '1', 'expected_output' => '1', 'is_hidden' => true, 'weight' => 1],
                ],
            ])
            ->assertRedirect();

        $question = Question::where('stem', 'Inline coding stem')->first();
        expect($question)->not->toBeNull();
        expect($section->sectionQuestions()->where('question_id', $question->id)->exists())->toBeTrue();
    });
});

describe('authorization', function () {
    test('user without quiz.edit cannot create sections', function () {
        $user = User::factory()->create();
        $role = Role::findOrCreate('Quiz Viewer', 'web');
        $role->syncPermissions(['quiz.view']);
        $user->assignRole($role);

        $quiz = Quiz::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.quizzes.sections.store', $quiz), [
                'title' => 'Should not work',
            ])
            ->assertForbidden();
    });

    test('user without quiz.edit cannot reorder sections', function () {
        $user = User::factory()->create();
        $role = Role::findOrCreate('Quiz Viewer 2', 'web');
        $role->syncPermissions(['quiz.view']);
        $user->assignRole($role);

        $quiz = Quiz::factory()->published()->create();
        $s1 = QuizSection::factory()->create(['quiz_id' => $quiz->id, 'position' => 0]);
        $s2 = QuizSection::factory()->create(['quiz_id' => $quiz->id, 'position' => 1]);

        $this->actingAs($user)
            ->post(route('admin.quizzes.sections.reorder', $quiz), [
                'section_ids' => [$s2->id, $s1->id],
            ])
            ->assertForbidden();

        // Order should be unchanged
        expect($s1->fresh()->position)->toBe(0);
        expect($s2->fresh()->position)->toBe(1);
    });
});

describe('audit log', function () {
    test('section creation is audited', function () {
        $quiz = Quiz::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.quizzes.sections.store', $quiz), [
                'title' => 'Audited Section',
            ]);

        $log = AuditLog::where('action', 'quiz.section_created')->latest('id')->first();
        expect($log)->not->toBeNull();
    });

    test('question attach is audited', function () {
        $quiz = Quiz::factory()->create();
        $section = QuizSection::factory()->create(['quiz_id' => $quiz->id]);
        $question = Question::factory()->singleSelect()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.quizzes.sections.questions.attach', [$quiz, $section]), [
                'question_id' => $question->id,
            ]);

        $log = AuditLog::where('action', 'quiz.question_attached')->latest('id')->first();
        expect($log)->not->toBeNull()
            ->and($log->changes['question_id'])->toBe($question->id);
    });
});

describe('builder page renders', function () {
    test('builder page loads with sections and questions', function () {
        $quiz = Quiz::factory()->create();
        $section = QuizSection::factory()->create(['quiz_id' => $quiz->id, 'title' => 'My Section']);
        $question = Question::factory()->singleSelect()->create(['stem' => 'My question stem']);
        $section->sectionQuestions()->create([
            'question_id' => $question->id,
            'question_version' => 1,
            'position' => 0,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.quizzes.builder', $quiz))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Quizzes/Edit/Builder')
                ->has('sections', 1)
                ->where('sections.0.title', 'My Section')
                ->has('sections.0.questions', 1)
            );
    });
});
