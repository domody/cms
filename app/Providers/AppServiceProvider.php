<?php

namespace App\Providers;

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\SetCurrentSite;
use App\Models\PersonalAccessToken;
use App\Models\Scopes\CurrentSite;
use App\Models\Site;
use App\View\Composers\MenuComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(CurrentSite::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Livewire update requests hit /livewire/update, not the page's URL;
        // re-run site membership against the original page URL so components
        // stay inside the site they were rendered in.
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        Livewire::addPersistentMiddleware([SetCurrentSite::class, EnsureUserIsActive::class]);

        RateLimiter::for('login', fn ($request) => Limit::perMinute(5)->by(
            Str::transliterate(Str::lower($request->string('email'))).'|'.$request->ip()
        ));

        // Per token, so one busy frontend can't starve another. Throttling can
        // run before the site check, so the user may not be a token owner.
        RateLimiter::for('cms-api', fn ($request) => Limit::perMinute(120)->by(
            ($request->user() instanceof Site ? $request->user()->currentAccessToken()?->id : null) ?? $request->ip()
        ));

        View::composer('layouts.admin', MenuComposer::class);
    }
}
