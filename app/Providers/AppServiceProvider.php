<?php

namespace App\Providers;

use App\Auth\TenantUnscopedEloquentUserProvider;
use App\Models\PersonalAccessToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
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
    }
}
