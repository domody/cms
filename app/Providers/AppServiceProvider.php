<?php

namespace App\Providers;

use App\Auth\TenantUnscopedEloquentUserProvider;
use App\Models\PersonalAccessToken;
use App\View\Composers\MenuComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

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
     */
    public function boot(): void
    {
        Auth::provider('tenant_unscoped_eloquent', fn ($app, array $config) => new TenantUnscopedEloquentUserProvider(
            $app['hash'],
            $config['model'],
        ));

        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        RateLimiter::for('login', fn ($request) => Limit::perMinute(5)->by(
            Str::transliterate(Str::lower($request->string('email'))).'|'.$request->ip()
        ));

        View::composer('layouts.admin', MenuComposer::class);
    }
}
