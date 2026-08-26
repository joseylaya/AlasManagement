<?php

namespace App\Providers;

use App\Contracts\AiProvider;
use App\Services\Ai\GeminiProvider;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
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
    }
}
