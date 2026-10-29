<?php

namespace App\Http\Middleware;

use App\Models\Scopes\CurrentSite;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Allows a request only if the user's access level (config/acl.php) grants the
 * controller action the route runs and, on a /{site}/... route, their role on
 * that site does too. Fail-closed: routes without a controller,
 * and actions no role lists, get 404 so the area's existence isn't revealed.
 *
 * Must run after authentication.
 */
class EnforceAcl
{
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();
        $controller = $route?->getControllerClass();

        // Invokable controllers have no "@method" in their action name.
        $method = str_contains($route?->getActionName() ?? '', '@') ? $route->getActionMethod() : '__invoke';

        $user = $request->user();

        abort_if($controller === null || ! $user?->canAccess($controller, $method), 404);

        // Inside a site, the user's role on it narrows what they may do.
        // SetCurrentSite has already run (it must precede route binding) and
        // consumed the {site} segment, so the site comes from CurrentSite.
        // Platform admins are not limited by site roles.
        $site = str_starts_with($route->uri(), '{site}/') ? app(CurrentSite::class)->get() : null;

        if ($site !== null && ! $user->isAdmin()) {
            $role = $user->roleOn($site);

            abort_if($role === null || ! $user->canAccessAs($role, $controller, $method), 404);
        }

        return $next($request);
    }
}
