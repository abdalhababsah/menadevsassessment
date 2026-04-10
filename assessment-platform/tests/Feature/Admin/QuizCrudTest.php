<?php

use App\Enums\QuizStatus;
use App\Models\AuditLog;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizSection;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->admin = User::factory()->superAdmin()->create();
});

describe('list quizzes', function () {
    test('renders index page with quizzes', function () {
        Quiz::factory()->count(3)->create();

        $this->actingAs($this->admin)
            ->get(route('admin.quizzes.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Quizzes/Index')
                ->has('quizzes', 3)
            );
    });

    test('non-authorized user gets 403', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.quizzes.index'))
            ->assertForbidden();
    });
});

describe('create quiz', function () {
    test('renders create page', function () {
        $this->actingAs($this->admin)
            ->get(route('admin.quizzes.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Quizzes/Create'));
    });

    test('creates a quiz and redirects to edit', function () {
        $this->actingAs($this->admin)
            ->post(route('admin.quizzes.store'), [
                'title' => 'My First Quiz',
                'description' => 'A description',
            ])
            ->assertRedirect();

        $quiz = Quiz::latest('id')->first();
        expect($quiz)->not->toBeNull()
            ->title->toBe('My First Quiz')
            ->description->toBe('A description')
            ->status->toBe(QuizStatus::Draft);
    });

    test('rejects empty title', function () {
        $this->actingAs($this->admin)
            ->post(route('admin.quizzes.store'), ['title' => ''])
            ->assertSessionHasErrors('title');
    });

    test('creating creates audit log entry', function () {
        $this->actingAs($this->admin)
            ->post(route('admin.quizzes.store'), ['title' => 'Audited Quiz']);

        $log = AuditLog::where('action', 'quiz.created')->latest('id')->first();
        expect($log)->not->toBeNull()
            ->and($log->changes['title'])->toBe('Audited Quiz');
    });
});

describe('update quiz settings', function () {
    test('updates all settings', function () {
        $quiz = Quiz::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('admin.quizzes.update', $quiz), [
                'title' => 'Updated Title',
                'description' => 'New description',
                'time_limit_seconds' => 3600,
                'passing_score' => 75.5,
                'randomize_questions' => true,
                'randomize_options' => true,
                'navigation_mode' => 'forward_only',
                'camera_enabled' => true,
                'anti_cheat_enabled' => true,
                'max_fullscreen_exits' => 5,
                'starts_at' => null,
                'ends_at' => null,
            ])
            ->assertRedirect();

        $quiz->refresh();
        expect($quiz->title)->toBe('Updated Title')
            ->and($quiz->time_limit_seconds)->toBe(3600)
            ->and((float) $quiz->passing_score)->toBe(75.5)
            ->and($quiz->randomize_questions)->toBeTrue()
            ->and($quiz->camera_enabled)->toBeTrue()
            ->and($quiz->max_fullscreen_exits)->toBe(5);
    });

    test('rejects ends_at before starts_at', function () {
        $quiz = Quiz::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('admin.quizzes.update', $quiz), [
                'title' => 'Test',
                'randomize_questions' => false,
                'randomize_options' => false,
                'navigation_mode' => 'free',
                'camera_enabled' => false,
                'anti_cheat_enabled' => false,
                'max_fullscreen_exits' => 3,
                'starts_at' => '2026-06-01 12:00:00',
                'ends_at' => '2026-05-01 12:00:00',
            ])
            ->assertSessionHasErrors('ends_at');
    });
});

describe('publish quiz', function () {
    test('blocks publish when no sections', function () {
        $quiz = Quiz::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.quizzes.publish', $quiz))
            ->assertRedirect();

        $quiz->refresh();
        expect($quiz->status)->toBe(QuizStatus::Draft);
    });

    test('blocks publish when section has no questions', function () {
        $quiz = Quiz::factory()->create();
        QuizSection::factory()->create(['quiz_id' => $quiz->id, 'title' => 'Empty Section']);

        $this->actingAs($this->admin)
            ->post(route('admin.quizzes.publish', $quiz))
            ->assertRedirect();

        $quiz->refresh();
        expect($quiz->status)->toBe(QuizStatus::Draft);
    });

    test('publishes quiz when sections and questions exist', function () {
        $quiz = Quiz::factory()->create();
        $section = QuizSection::factory()->create(['quiz_id' => $quiz->id]);
        $question = Question::factory()->singleSelect()->create();
        $section->questions()->attach($question->id, ['question_version' => 1, 'position' => 0]);

        $this->actingAs($this->admin)
            ->post(route('admin.quizzes.publish', $quiz))
            ->assertRedirect();

        expect($quiz->fresh()->status)->toBe(QuizStatus::Published);
    });

    test('publish creates audit log', function () {
        $quiz = Quiz::factory()->create();
        $section = QuizSection::factory()->create(['quiz_id' => $quiz->id]);
        $question = Question::factory()->singleSelect()->create();
        $section->questions()->attach($question->id, ['question_version' => 1, 'position' => 0]);

        $this->actingAs($this->admin)
            ->post(route('admin.quizzes.publish', $quiz));

        $log = AuditLog::where('action', 'quiz.published')->latest('id')->first();
        expect($log)->not->toBeNull();
    });
});

describe('unpublish quiz', function () {
    test('moves a published quiz back to draft', function () {
        $quiz = Quiz::factory()->published()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.quizzes.unpublish', $quiz))
            ->assertRedirect();

        expect($quiz->fresh()->status)->toBe(QuizStatus::Draft);
    });
});

describe('duplicate quiz', function () {
    test('clones quiz with sections and section_questions', function () {
        $quiz = Quiz::factory()->create([
            'title' => 'Original Quiz',
            'time_limit_seconds' => 1800,
            'passing_score' => 70,
        ]);
        $section1 = QuizSection::factory()->create(['quiz_id' => $quiz->id, 'title' => 'Section 1', 'position' => 0]);
        $section2 = QuizSection::factory()->create(['quiz_id' => $quiz->id, 'title' => 'Section 2', 'position' => 1]);

        $q1 = Question::factory()->singleSelect()->create();
        $q2 = Question::factory()->coding()->create();
        $section1->questions()->attach($q1->id, ['question_version' => 1, 'position' => 0]);
        $section1->questions()->attach($q2->id, ['question_version' => 1, 'position' => 1]);
        $section2->questions()->attach($q1->id, ['question_version' => 1, 'position' => 0]);

        $this->actingAs($this->admin)
            ->post(route('admin.quizzes.duplicate', $quiz))
            ->assertRedirect();

        $copy = Quiz::where('title', 'Original Quiz (Copy)')->first();
        expect($copy)->not->toBeNull()
            ->status->toBe(QuizStatus::Draft)
            ->time_limit_seconds->toBe(1800);

        $copy->load('sections.sectionQuestions');
        expect($copy->sections)->toHaveCount(2);
        expect($copy->sections[0]->title)->toBe('Section 1');
        expect($copy->sections[0]->sectionQuestions)->toHaveCount(2);
        expect($copy->sections[1]->sectionQuestions)->toHaveCount(1);

        // Original is untouched
        expect($quiz->fresh()->title)->toBe('Original Quiz');
    });

    test('duplicate creates audit log', function () {
        $quiz = Quiz::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.quizzes.duplicate', $quiz));

        $log = AuditLog::where('action', 'quiz.duplicated')->latest('id')->first();
        expect($log)->not->toBeNull()
            ->and($log->changes['source_quiz_id'])->toBe($quiz->id);
    });
});

describe('delete quiz', function () {
    test('soft deletes a quiz', function () {
        $quiz = Quiz::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('admin.quizzes.destroy', $quiz))
            ->assertRedirect(route('admin.quizzes.index'));

        expect(Quiz::find($quiz->id))->toBeNull();
        expect(Quiz::withTrashed()->find($quiz->id))->not->toBeNull();
    });

    test('delete creates audit log', function () {
        $quiz = Quiz::factory()->create(['title' => 'Doomed']);

        $this->actingAs($this->admin)
            ->delete(route('admin.quizzes.destroy', $quiz));

        $log = AuditLog::where('action', 'quiz.deleted')->latest('id')->first();
        expect($log)->not->toBeNull()
            ->and($log->changes['title'])->toBe('Doomed');
    });
});

describe('authorization', function () {
    test('user without quiz.create cannot create', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.quizzes.store'), ['title' => 'X'])
            ->assertForbidden();
    });

    test('user without quiz.publish cannot publish', function () {
        $user = User::factory()->create();
        Role::findOrCreate('Quiz Viewer', 'web')
            ->syncPermissions(['quiz.view']);
        $user->assignRole('Quiz Viewer');

        $quiz = Quiz::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.quizzes.publish', $quiz))
            ->assertForbidden();
    });
});
