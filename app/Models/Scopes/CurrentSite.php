<?php

namespace App\Models\Scopes;

use App\Models\Site;

/**
 * Holds the site the current request is "inside". Registered as a scoped
 * singleton, so it is reset between requests/jobs. It is only ever set by
 * App\Http\Middleware\SetCurrentSite, after confirming the authenticated
 * user is a member of the site (or an admin); SiteScope and BelongsToSite read it.
 */
class CurrentSite
{
    private ?Site $site = null;

    public function set(Site $site): void
    {
        $this->site = $site;
    }

    public function get(): ?Site
    {
        return $this->site;
    }

    public function id(): ?int
    {
        return $this->site?->id;
    }

    public function forget(): void
    {
        $this->site = null;
    }
}
