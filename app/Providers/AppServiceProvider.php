<?php

namespace App\Providers;

use App\Support\Contracts\VoteTally;
use App\Support\RedisVoteTally;
use App\Support\SpendingCap;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(VoteTally::class, RedisVoteTally::class);

        $this->app->singleton(SpendingCap::class, fn (): SpendingCap => new SpendingCap(
            (int) config('plated.ai_daily_dish_cap'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
