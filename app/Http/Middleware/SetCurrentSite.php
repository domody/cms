<?php

namespace App\Http\Middleware;

use App\Models\Scopes\CurrentSite;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Puts the request "inside" the site named by the {site} URL segment, but
 * only if the authenticated user is a member of it (admins may enter any
 * site). A site the user can't enter is indistinguishable from one that
 * doesn't exist (404).
 *
 * Must run after authentication and before route model binding, so that
 * bound models ({page:slug}) are resolved through SiteScope. It is also
 * registered as Livewire persistent middleware so component update requests
 * are placed inside the same site.
 */
class SetCurrentSite
{
    public function __construct(private CurrentSite $currentSite) {}

    public function handle(Request $request, Closure $next): Response
    {
        $site = $request->user()
            ?->accessibleSites()
            ->where('sites.slug', $request->route('site'))
            ->first();

        abort_if($site === null, 404);

        $this->currentSite->set($site);

        // Links generated inside the site (route('pages.index')) get the
        // {site} segment filled in automatically.
        URL::defaults(['site' => $site->slug]);

        // The slug is consumed here; controllers and bound models don't take it.
        $request->route()->forgetParameter('site');

        return $next($request);
    }
}
