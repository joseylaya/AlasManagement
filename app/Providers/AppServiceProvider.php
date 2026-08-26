<?php

namespace App\Providers;

use App\Contracts\AiProvider;
use App\Services\Ai\GeminiProvider;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use App\Models\OwnerCapitalInjection;
use App\Policies\OwnerCapitalInjectionPolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AiProvider::class, GeminiProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(OwnerCapitalInjection::class, OwnerCapitalInjectionPolicy::class);

        // Keep Livewire updates on a stable, same-origin URL. This prevents an
        // already-open PWA page from posting to a previous hashed endpoint
        // after a deploy, which Livewire correctly rejects with a 404.
        Livewire::setUpdateRoute(function ($handle) {
            return Route::post('/livewire/update', $handle);
        });
    }
}
