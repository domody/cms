<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs out a user who has been disabled since they logged in, so disabling
 * takes effect on their next request rather than when their session expires.
 * Also registered as Livewire persistent middleware for component updates.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isDisabled()) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort_if($request->hasHeader('X-Livewire'), 401);

            return redirect()->route('login');
        }

        return $next($request);
    }
}
