<?php

namespace App\Providers;

use App\Contracts\AiProviders\AiResponseGenerator;
use App\Contracts\CodeRunners\CodeRunner;
use App\Contracts\Storage\MediaStorage;
use App\Models\User;
use App\Policies\RolePolicy;
use App\Services\AiProviders\Anthropic\AnthropicResponseGenerator;
use App\Services\CodeRunners\SandboxedCodeRunner;
use App\Services\QuestionBank\QuestionVersioningService;
use App\Services\Scoring\QuizScoringService;
use App\Services\Storage\LocalMediaStorage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AiResponseGenerator::class, AnthropicResponseGenerator::class);
        $this->app->bind(CodeRunner::class, SandboxedCodeRunner::class);
        $this->app->bind(MediaStorage::class, LocalMediaStorage::class);

        $this->app->singleton(QuestionVersioningService::class);
        $this->app->singleton(QuizScoringService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Gate::policy(Role::class, RolePolicy::class);

        Gate::before(function (User $user) {
            if ($user->is_super_admin) {
                return true;
            }

            return null;
        });
    }
}
