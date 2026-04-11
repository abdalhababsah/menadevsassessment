<?php

use App\Enums\AttemptStatus;
use App\Enums\RlhfReviewStatus;
use App\Models\AttemptCameraSnapshot;
use App\Models\AttemptSuspiciousEvent;
use App\Models\Candidate;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

function makeReviewer(array $permissions): User
{
    $user = User::factory()->create();
    $role = Role::findOrCreate('Reviewer '.uniqid(), 'web');
    $role->syncPermissions($permissions);
    $user->assignRole($role);

    return $user;
}

describe('results index & ranking', function () {
    test('index lists all quizzes for users with results.view', function () {
        $user = makeReviewer(['results.view']);
        Quiz::factory()->count(3)->create();

        $this->actingAs($user)
            ->get('/admin/results')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Results/Index')
                ->has('quizzes', 3)
            );
    });

    test('index is blocked without results.view', function () {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/results')->assertForbidden();
    });

    test('show ranks attempts by final_score DESC NULLS LAST, then auto_score DESC', function () {
        $user = makeReviewer(['results.view']);
        $quiz = Quiz::factory()->create();

        // Attempt A: final 90, auto 85
        $a = QuizAttempt::factory()->submitted()->create([
            'quiz_id' => $quiz->id,
            'candidate_id' => Candidate::factory()->create(['name' => 'Alpha'])->id,
            'auto_score' => 85,
            'final_score' => 90,
            'rlhf_review_status' => RlhfReviewStatus::Completed,
        ]);

        // Attempt B: final 95, auto 80 (should rank #1)
        $b = QuizAttempt::factory()->submitted()->create([
            'quiz_id' => $quiz->id,
            'candidate_id' => Candidate::factory()->create(['name' => 'Bravo'])->id,
            'auto_score' => 80,
            'final_score' => 95,
            'rlhf_review_status' => RlhfReviewStatus::Completed,
        ]);

        // Attempt C: final NULL, auto 88 (ranks LAST because NULLS go last)
        $c = QuizAttempt::factory()->submitted()->create([
            'quiz_id' => $quiz->id,
            'candidate_id' => Candidate::factory()->create(['name' => 'Charlie'])->id,
            'auto_score' => 88,
            'final_score' => null,
            'rlhf_review_status' => RlhfReviewStatus::Pending,
        ]);

        $this->actingAs($user)
            ->get("/admin/results/{$quiz->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Results/QuizResults')
                ->has('attempts', 3)
                ->where('attempts.0.id', $b->id)   // final 95
                ->where('attempts.0.rank', 1)
                ->where('attempts.1.id', $a->id)   // final 90
                ->where('attempts.1.rank', 2)
                ->where('attempts.2.id', $c->id)   // null final
                ->where('attempts.2.rank', 3)
            );
    });

    test('only submitted attempts appear in results', function () {
        $user = makeReviewer(['results.view']);
        $quiz = Quiz::factory()->create();

        QuizAttempt::factory()->submitted()->create(['quiz_id' => $quiz->id]);
        QuizAttempt::factory()->create([
            'quiz_id' => $quiz->id,
            'status' => AttemptStatus::InProgress,
            'submitted_at' => null,
        ]);

        $this->actingAs($user)
            ->get("/admin/results/{$quiz->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('attempts', 1));
    });
});

describe('permission gates', function () {
    test('suspicious counts null unless user has results.viewSuspicious', function () {
        $user = makeReviewer(['results.view']);
        $quiz = Quiz::factory()->create();
        $attempt = QuizAttempt::factory()->submitted()->create(['quiz_id' => $quiz->id]);
        AttemptSuspiciousEvent::factory()->count(3)->create(['quiz_attempt_id' => $attempt->id]);

        $this->actingAs($user)
            ->get("/admin/results/{$quiz->id}")
            ->assertInertia(fn ($page) => $page
                ->where('attempts.0.suspicious_events_count', null)
                ->where('permissions.view_suspicious', false)
            );
    });

    test('suspicious counts visible with results.viewSuspicious', function () {
        $user = makeReviewer(['results.view', 'results.viewSuspicious']);
        $quiz = Quiz::factory()->create();
        $attempt = QuizAttempt::factory()->submitted()->create(['quiz_id' => $quiz->id]);
        AttemptSuspiciousEvent::factory()->count(3)->create(['quiz_attempt_id' => $attempt->id]);

        $this->actingAs($user)
            ->get("/admin/results/{$quiz->id}")
            ->assertInertia(fn ($page) => $page
                ->where('attempts.0.suspicious_events_count', 3)
                ->where('permissions.view_suspicious', true)
            );
    });

    test('attempt drill-down hides suspicious events without permission', function () {
        $user = makeReviewer(['results.view']);
        $quiz = Quiz::factory()->create();
        $attempt = QuizAttempt::factory()->submitted()->create(['quiz_id' => $quiz->id]);
        AttemptSuspiciousEvent::factory()->count(2)->create(['quiz_attempt_id' => $attempt->id]);

        $this->actingAs($user)
            ->get("/admin/results/attempt/{$attempt->id}")
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Results/AttemptDetail')
                ->has('suspicious_events', 0)
                ->where('permissions.view_suspicious', false)
            );
    });

    test('attempt drill-down shows suspicious events with permission', function () {
        $user = makeReviewer(['results.view', 'results.viewSuspicious']);
        $quiz = Quiz::factory()->create();
        $attempt = QuizAttempt::factory()->submitted()->create(['quiz_id' => $quiz->id]);
        AttemptSuspiciousEvent::factory()->count(2)->create(['quiz_attempt_id' => $attempt->id]);

        $this->actingAs($user)
            ->get("/admin/results/attempt/{$attempt->id}")
            ->assertInertia(fn ($page) => $page->has('suspicious_events', 2));
    });

    test('snapshots hidden without results.viewSnapshots', function () {
        $user = makeReviewer(['results.view', 'results.viewSuspicious']);
        $quiz = Quiz::factory()->create();
        $attempt = QuizAttempt::factory()->submitted()->create(['quiz_id' => $quiz->id]);
        AttemptCameraSnapshot::factory()->count(2)->create(['quiz_attempt_id' => $attempt->id]);

        $this->actingAs($user)
            ->get("/admin/results/attempt/{$attempt->id}")
            ->assertInertia(fn ($page) => $page
                ->has('snapshots', 0)
                ->where('permissions.view_snapshots', false)
            );
    });

    test('snapshots visible with results.viewSnapshots', function () {
        $user = makeReviewer(['results.view', 'results.viewSnapshots']);
        $quiz = Quiz::factory()->create();
        $attempt = QuizAttempt::factory()->submitted()->create(['quiz_id' => $quiz->id]);
        AttemptCameraSnapshot::factory()->count(2)->create(['quiz_attempt_id' => $attempt->id]);

        $this->actingAs($user)
            ->get("/admin/results/attempt/{$attempt->id}")
            ->assertInertia(fn ($page) => $page->has('snapshots', 2));
    });
});

describe('export CSV', function () {
    test('export requires results.export', function () {
        $user = makeReviewer(['results.view']);
        $quiz = Quiz::factory()->create();

        $this->actingAs($user)->get("/admin/results/{$quiz->id}/export")->assertForbidden();
    });

    test('export returns CSV with ranked rows', function () {
        $user = makeReviewer(['results.view', 'results.export']);
        $quiz = Quiz::factory()->create();

        QuizAttempt::factory()->submitted()->create([
            'quiz_id' => $quiz->id,
            'candidate_id' => Candidate::factory()->create(['name' => 'Alice', 'email' => 'alice@test.com'])->id,
            'auto_score' => 80,
            'final_score' => 90,
        ]);
        QuizAttempt::factory()->submitted()->create([
            'quiz_id' => $quiz->id,
            'candidate_id' => Candidate::factory()->create(['name' => 'Bob', 'email' => 'bob@test.com'])->id,
            'auto_score' => 75,
            'final_score' => 95,
        ]);

        $response = $this->actingAs($user)->get("/admin/results/{$quiz->id}/export");

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();
        expect($csv)->toContain('rank,candidate_name,candidate_email')
            ->and($csv)->toContain('Bob')
            ->and($csv)->toContain('Alice');

        $lines = array_filter(explode("\n", $csv));
        // Header + 2 rows.
        expect(count($lines))->toBe(3);

        // Bob (final 95) ranks first.
        expect($lines[1])->toContain('1,Bob');
        expect($lines[2])->toContain('2,Alice');
    });
});

describe('attempt drill-down data', function () {
    test('loads candidate, quiz, answers, and permissions', function () {
        $user = makeReviewer(['results.view', 'results.viewSuspicious', 'results.viewSnapshots']);
        $quiz = Quiz::factory()->create(['title' => 'Mid-year review']);
        $candidate = Candidate::factory()->create(['name' => 'Dana']);
        $attempt = QuizAttempt::factory()->submitted()->create([
            'quiz_id' => $quiz->id,
            'candidate_id' => $candidate->id,
            'auto_score' => 88,
            'final_score' => 88,
        ]);

        $this->actingAs($user)
            ->get("/admin/results/attempt/{$attempt->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Results/AttemptDetail')
                ->where('attempt.id', $attempt->id)
                ->where('attempt.final_score', 88)
                ->where('quiz.title', 'Mid-year review')
                ->where('candidate.name', 'Dana')
                ->where('permissions.view_suspicious', true)
                ->where('permissions.view_snapshots', true)
            );
    });
});
