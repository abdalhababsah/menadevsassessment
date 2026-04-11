<?php

use App\Actions\Attempts\AutoSubmitExpiredAttemptsAction;
use App\Models\AttemptCameraSnapshot;
use App\Models\Candidate;
use App\Models\QuizInvitation;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('attempts:auto-submit-expired', function (AutoSubmitExpiredAttemptsAction $action) {
    $count = $action->handle();

    $this->info("Auto-submitted {$count} expired attempt(s).");
})->purpose('Auto-submit expired candidate quiz attempts.');

Artisan::command('invitations:cleanup-expired', function () {
    $count = QuizInvitation::query()
        ->whereNotNull('expires_at')
        ->where('expires_at', '<', now()->subDays(30))
        ->whereNull('revoked_at')
        ->update(['revoked_at' => now()]);

    $this->info("Revoked {$count} long-expired invitation(s).");
})->purpose('Revoke invitations whose expires_at is more than 30 days in the past.');

Artisan::command('candidates:cleanup-expired-verifications', function () {
    $count = Candidate::query()
        ->whereNull('email_verified_at')
        ->where('created_at', '<', now()->subDays(7))
        ->delete();

    $this->info("Removed {$count} unverified stale candidate account(s).");
})->purpose('Purge candidate accounts whose email verification never completed.');

Artisan::command('snapshots:prune-old {--days=30}', function (int $days = 30) {
    $cutoff = now()->subDays((int) $this->option('days'));
    $count = AttemptCameraSnapshot::query()
        ->where('captured_at', '<', $cutoff)
        ->delete();

    $this->info("Pruned {$count} camera snapshot(s) older than {$days} days.");
})->purpose('Delete old camera snapshots past the retention window.');

Schedule::command('attempts:auto-submit-expired')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('invitations:cleanup-expired')
    ->daily()
    ->withoutOverlapping();

Schedule::command('candidates:cleanup-expired-verifications')
    ->hourly()
    ->withoutOverlapping();

Schedule::command('snapshots:prune-old --days=30')
    ->daily()
    ->withoutOverlapping();

Schedule::command('horizon:snapshot')
    ->everyFiveMinutes();
