<?php

use App\Models\Question;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->admin = User::factory()->superAdmin()->create();
});

describe('filter by type', function () {
    test('filters questions by type', function () {
        Question::factory()->singleSelect()->count(3)->create();
        Question::factory()->coding()->count(2)->create();

        $this->actingAs($this->admin)
            ->get(route('admin.questions.index', ['filter[type]' => 'coding']))
            ->assertInertia(fn ($page) => $page
                ->component('Admin/QuestionBank/Index')
                ->where('questions.total', 2)
            );
    });
});

describe('filter by difficulty', function () {
    test('filters questions by difficulty', function () {
        Question::factory()->easy()->count(2)->create();
        Question::factory()->hard()->count(4)->create();

        $this->actingAs($this->admin)
            ->get(route('admin.questions.index', ['filter[difficulty]' => 'hard']))
            ->assertInertia(fn ($page) => $page
                ->where('questions.total', 4)
            );
    });
});

describe('filter by tag', function () {
    test('filters questions by tag ids', function () {
        $phpTag = Tag::factory()->create(['name' => 'php']);
        $jsTag = Tag::factory()->create(['name' => 'javascript']);

        $phpQuestion = Question::factory()->create();
        $phpQuestion->tags()->attach($phpTag);

        $jsQuestion = Question::factory()->create();
        $jsQuestion->tags()->attach($jsTag);

        Question::factory()->count(2)->create();

        $this->actingAs($this->admin)
            ->get(route('admin.questions.index', ['filter[tags]' => $phpTag->id]))
            ->assertInertia(fn ($page) => $page
                ->where('questions.total', 1)
            );
    });
});

describe('search by stem keyword', function () {
    test('searches stems with q filter', function () {
        Question::factory()->create(['stem' => 'What is recursion in computer science?']);
        Question::factory()->create(['stem' => 'Explain dependency injection']);
        Question::factory()->create(['stem' => 'How does recursion work?']);

        $this->actingAs($this->admin)
            ->get(route('admin.questions.index', ['filter[q]' => 'recursion']))
            ->assertInertia(fn ($page) => $page
                ->where('questions.total', 2)
            );
    });
});

describe('pagination', function () {
    test('returns 15 questions per page', function () {
        Question::factory()->count(20)->create();

        $this->actingAs($this->admin)
            ->get(route('admin.questions.index'))
            ->assertInertia(fn ($page) => $page
                ->where('questions.total', 20)
                ->where('questions.current_page', 1)
                ->where('questions.last_page', 2)
                ->has('questions.data', 15)
            );
    });

    test('returns second page', function () {
        Question::factory()->count(20)->create();

        $this->actingAs($this->admin)
            ->get(route('admin.questions.index', ['page' => 2]))
            ->assertInertia(fn ($page) => $page
                ->where('questions.current_page', 2)
                ->has('questions.data', 5)
            );
    });
});

describe('sorting', function () {
    test('sorts by created_at descending by default', function () {
        $oldest = Question::factory()->create(['created_at' => now()->subDays(3)]);
        $newest = Question::factory()->create(['created_at' => now()]);
        $middle = Question::factory()->create(['created_at' => now()->subDay()]);

        $this->actingAs($this->admin)
            ->get(route('admin.questions.index'))
            ->assertInertia(fn ($page) => $page
                ->where('questions.data.0.id', $newest->id)
                ->where('questions.data.1.id', $middle->id)
                ->where('questions.data.2.id', $oldest->id)
            );
    });

    test('sorts by created_at ascending when requested', function () {
        $oldest = Question::factory()->create(['created_at' => now()->subDays(3)]);
        $newest = Question::factory()->create(['created_at' => now()]);

        $this->actingAs($this->admin)
            ->get(route('admin.questions.index', ['sort' => 'created_at']))
            ->assertInertia(fn ($page) => $page
                ->where('questions.data.0.id', $oldest->id)
                ->where('questions.data.1.id', $newest->id)
            );
    });
});

describe('combined filters', function () {
    test('combines multiple filters', function () {
        Question::factory()->coding()->hard()->count(2)->create();
        Question::factory()->coding()->easy()->count(3)->create();
        Question::factory()->singleSelect()->hard()->count(4)->create();

        $this->actingAs($this->admin)
            ->get(route('admin.questions.index', [
                'filter[type]' => 'coding',
                'filter[difficulty]' => 'hard',
            ]))
            ->assertInertia(fn ($page) => $page
                ->where('questions.total', 2)
            );
    });

    test('combines search and type filter', function () {
        Question::factory()->coding()->create(['stem' => 'Implement quicksort algorithm']);
        Question::factory()->coding()->create(['stem' => 'Implement mergesort']);
        Question::factory()->singleSelect()->create(['stem' => 'What is quicksort?']);

        $this->actingAs($this->admin)
            ->get(route('admin.questions.index', [
                'filter[type]' => 'coding',
                'filter[q]' => 'quicksort',
            ]))
            ->assertInertia(fn ($page) => $page
                ->where('questions.total', 1)
            );
    });
});

describe('authorization', function () {
    test('user without questionbank.view cannot access', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.questions.index'))
            ->assertForbidden();
    });

    test('user with questionbank.view permission can access', function () {
        $role = Role::findOrCreate('Question Viewer', 'web');
        $role->syncPermissions(['questionbank.view']);

        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('admin.questions.index'))
            ->assertOk();
    });

    test('guests are redirected to login', function () {
        $this->get(route('admin.questions.index'))
            ->assertRedirect(route('login'));
    });
});

describe('response shape', function () {
    test('returns tags and creators for filter dropdowns', function () {
        Tag::factory()->count(3)->create();
        $creator = User::factory()->create();
        Question::factory()->create(['created_by' => $creator->id]);

        $this->actingAs($this->admin)
            ->get(route('admin.questions.index'))
            ->assertInertia(fn ($page) => $page
                ->has('tags', 3)
                ->has('creators')
            );
    });

    test('eager loads tags and creator', function () {
        $creator = User::factory()->create(['name' => 'Author Name']);
        $tag = Tag::factory()->create(['name' => 'algorithms']);
        $question = Question::factory()->create(['created_by' => $creator->id]);
        $question->tags()->attach($tag);

        $this->actingAs($this->admin)
            ->get(route('admin.questions.index'))
            ->assertInertia(fn ($page) => $page
                ->where('questions.data.0.creator.name', 'Author Name')
                ->where('questions.data.0.tags.0.name', 'algorithms')
            );
    });
});
