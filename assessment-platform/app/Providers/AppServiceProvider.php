<?php

namespace App\Providers;

use App\Contracts\AiProviders\AiResponseGenerator;
use App\Contracts\CodeRunners\CodeRunner;
use App\Contracts\Storage\MediaStorage;
use App\Services\AiProviders\Anthropic\AnthropicResponseGenerator;
use App\Services\CodeRunners\SandboxedCodeRunner;
use App\Services\Storage\LocalMediaStorage;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
