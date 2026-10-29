<?php

namespace App\Models\Scopes;

use RuntimeException;

/**
 * A site-owned model was queried with no current site. This is always a bug
 * (missing middleware, or a job/command that never entered a site), never a
 * normal "no rows" case.
 */
class NoCurrentSiteException extends RuntimeException
{
    public static function forModel(string $model): self
    {
        return new self("Queried [{$model}] with no current site. Run inside a site (SetCurrentSite / SetCurrentSiteFromToken), set CurrentSite explicitly, or use withoutGlobalScope(SiteScope::class) for a deliberate cross-site query.");
    }
}
