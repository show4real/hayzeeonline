<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * Laravel 11 folded the Auth, Broadcast, Event and Route providers into
     * this one, so what used to live in RouteServiceProvider is here now.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * The limiter named by the `throttle:api` middleware in bootstrap/app.php.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
