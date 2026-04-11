<?php

use App\Actions\Attempts\RecordSuspiciousEventAction;
use App\Enums\AttemptStatus;
use App\Enums\SuspiciousEventType;
use App\Models\AttemptSuspiciousEvent;
use App\Models\Candidate;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizInvitation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->candidate = Candidate::factory()->verified()->create();
    $this->quiz = Quiz::factory()->published()->create([
        'anti_cheat_enabled' => true,
        'max_fullscreen_exits' => 3,
    ]);
    $this->invitation = QuizInvitation::factory()->create(['quiz_id' => $this->quiz->id]);
    $this->attempt = QuizAttempt::factory()->create([
        'quiz_id' => $this->quiz->id,
        'candidate_id' => $this->candidate->id,
        'invitation_id' => $this->invitation->id,
        'status' => AttemptStatus::InProgress,
    ]);

    $this->actingAs($this->candidate, 'candidate')
        ->withSession(['quiz_attempt_id' => $this->attempt->id]);
});

describe('suspicious event recording', function () {
    test('POST /api/quiz/suspicious-event records a fullscreen_exit event', function () {
        $this->postJson('/api/quiz/suspicious-event', [
            'event_type' => 'fullscreen_exit',
            'metadata' => ['timestamp' => now()->toIso8601String()],
        ])
            ->assertStatus(201)
            ->assertJsonPath('recorded', true)
            ->assertJsonPath('attempt_status', 'in_progress');

        expect(
            AttemptSuspiciousEvent::query()
                ->where('quiz_attempt_id', $this->attempt->id)
                ->where('event_type', SuspiciousEventType::FullscreenExit)
                ->count()
        )->toBe(1);
    });

    test('records a tab_switch event', function () {
        $this->postJson('/api/quiz/suspicious-event', [
            'event_type' => 'tab_switch',
        ])
            ->assertStatus(201)
            ->assertJsonPath('recorded', true);

        expect(
            AttemptSuspiciousEvent::query()
                ->where('quiz_attempt_id', $this->attempt->id)
                ->where('event_type', SuspiciousEventType::TabSwitch)
                ->count()
        )->toBe(1);
    });

    test('rejects an unknown event_type', function () {
        $this->postJson('/api/quiz/suspicious-event', [
            'event_type' => 'laser_eyes',
        ])->assertStatus(422);
    });

    test('unauthenticated request is rejected', function () {
        $this->app['auth']->guard('candidate')->logout();

        $this->postJson('/api/quiz/suspicious-event', [
            'event_type' => 'tab_switch',
        ])->assertStatus(401);
    });
});

describe('fullscreen-exit lock threshold', function () {
    test('auto-submits attempt when fullscreen exits reach the max', function () {
        // Fire 2 exits — below the limit of 3 — attempt stays in progress.
        for ($i = 0; $i < 2; $i++) {
            $this->postJson('/api/quiz/suspicious-event', ['event_type' => 'fullscreen_exit'])
                ->assertStatus(201)
                ->assertJsonPath('attempt_status', 'in_progress');
        }
        expect($this->attempt->fresh()->status)->toBe(AttemptStatus::InProgress);

        // Third exit hits the threshold.
        $this->postJson('/api/quiz/suspicious-event', ['event_type' => 'fullscreen_exit'])
            ->assertStatus(201)
            ->assertJsonPath('attempt_status', 'auto_submitted');

        expect($this->attempt->fresh()->status)->toBe(AttemptStatus::AutoSubmitted);
    });

    test('non-fullscreen events do not trigger lock', function () {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/quiz/suspicious-event', ['event_type' => 'tab_switch'])
                ->assertStatus(201);
        }

        expect($this->attempt->fresh()->status)->toBe(AttemptStatus::InProgress);
    });

    test('quiz with max_fullscreen_exits 0 never auto-submits on exit', function () {
        $this->quiz->update(['max_fullscreen_exits' => 0]);

        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/quiz/suspicious-event', ['event_type' => 'fullscreen_exit'])
                ->assertStatus(201);
        }

        expect($this->attempt->fresh()->status)->toBe(AttemptStatus::InProgress);
    });

    test('cumulative count includes all prior exits in the same attempt', function () {
        // Seed 2 existing exits directly.
        AttemptSuspiciousEvent::factory()->count(2)->create([
            'quiz_attempt_id' => $this->attempt->id,
            'event_type' => SuspiciousEventType::FullscreenExit,
        ]);

        // The third exit (posted now) should trigger lock.
        $this->postJson('/api/quiz/suspicious-event', ['event_type' => 'fullscreen_exit'])
            ->assertStatus(201)
            ->assertJsonPath('attempt_status', 'auto_submitted');

        expect($this->attempt->fresh()->status)->toBe(AttemptStatus::AutoSubmitted);
    });
});

describe('RecordSuspiciousEventAction', function () {
    test('handle() returns the created event model', function () {
        $action = app(RecordSuspiciousEventAction::class);

        $event = $action->handle($this->attempt, SuspiciousEventType::WindowBlur, ['foo' => 'bar']);

        expect($event)->toBeInstanceOf(AttemptSuspiciousEvent::class)
            ->and($event->event_type)->toBe(SuspiciousEventType::WindowBlur)
            ->and($event->metadata)->toBe(['foo' => 'bar']);
    });

    test('already-submitted attempt is not double-submitted', function () {
        $this->attempt->update(['status' => AttemptStatus::Submitted]);

        // Trigger enough exits — LockOrAutoSubmitAttemptAction should be a no-op.
        $action = app(RecordSuspiciousEventAction::class);
        for ($i = 0; $i < 5; $i++) {
            $action->handle($this->attempt, SuspiciousEventType::FullscreenExit);
        }

        expect($this->attempt->fresh()->status)->toBe(AttemptStatus::Submitted);
    });
});

describe('camera snapshot upload', function () {
    test('POST /api/quiz/camera-snapshot stores the file and returns 201', function () {
        Storage::fake('local');

        $file = UploadedFile::fake()->image('snapshot.jpg', 320, 240);

        $this->post('/api/quiz/camera-snapshot', ['snapshot' => $file], ['Accept' => 'application/json'])
            ->assertStatus(201)
            ->assertJsonStructure(['id', 'captured_at']);

        expect(
            $this->attempt->cameraSnapshots()->count()
        )->toBe(1);

        $snapshot = $this->attempt->cameraSnapshots()->first();
        Storage::disk('local')->assertExists($snapshot->url);
    });

    test('rejects a non-image file', function () {
        $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        $this->post(
            '/api/quiz/camera-snapshot',
            ['snapshot' => $file],
            ['Accept' => 'application/json'],
        )->assertStatus(422);
    });

    test('rejects missing file', function () {
        $this->postJson('/api/quiz/camera-snapshot', [])
            ->assertStatus(422);
    });

    test('candidate cannot upload snapshots for another candidate attempt', function () {
        $otherAttempt = QuizAttempt::factory()->create([
            'quiz_id' => $this->quiz->id,
            'status' => AttemptStatus::InProgress,
        ]);

        // Log in as other candidate but keep session pointing to our attempt.
        $this->actingAs($otherAttempt->candidate, 'candidate')
            ->withSession(['quiz_attempt_id' => $this->attempt->id]);

        $file = UploadedFile::fake()->image('snap.jpg');

        $this->post(
            '/api/quiz/camera-snapshot',
            ['snapshot' => $file],
            ['Accept' => 'application/json'],
        )->assertStatus(403);
    });
});
