<?php

namespace App\Http\Middleware;

use App\Models\PersonalAccessToken;
use App\Models\Scopes\CurrentSite;
use App\Models\Site;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API counterpart of SetCurrentSite: the site comes from the token, not the
 * URL. A token is owned by a site (it authenticates a frontend as the site
 * itself, never as a person), so the authenticated "user" must be a Site and
 * the request is placed inside it. Anything else - a browser session, a
 * token owned by something other than a site - gets 403.
 */
class SetCurrentSiteFromToken
{
    public function __construct(private CurrentSite $currentSite) {}

    public function handle(Request $request, Closure $next): Response
    {
        $site = $request->user();
        $token = $site instanceof Site ? $site->currentAccessToken() : null;

        abort_unless(
            $token instanceof PersonalAccessToken && $token->site_id === $site->id,
            403,
            'This token is not bound to a site.',
        );

        $this->currentSite->set($site);

        return $next($request);
    }
}
