<?php

namespace App\Models\Concerns;

use App\Models\Scopes\CurrentSite;
use App\Models\Scopes\SiteScope;
use App\Models\Site;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/**
 * Marks a model as site-owned: every query is automatically scoped to the
 * site the request is currently inside (see SiteScope), new records are
 * stamped with it, and it cannot be reassigned to a different site.
 * Any model storing site-specific data should use this trait instead of
 * filtering by site_id manually in queries.
 */
trait BelongsToSite
{
    protected static function bootBelongsToSite(): void
    {
        static::addGlobalScope(new SiteScope);

        static::creating(function ($model): void {
            // Inside a site: the site is never taken from client input, it's
            // always the current one - mass assignment can't be used to
            // create a record under a different site.
            // Outside any site (console, seeders, queue jobs): the caller
            // must set site_id explicitly, since there is no "current"
            // site to infer it from.
            if ($siteId = app(CurrentSite::class)->id()) {
                $model->site_id = $siteId;
            }
        });

        static::updating(function ($model): void {
            $siteId = app(CurrentSite::class)->id();

            if ($siteId === null) {
                return;
            }

            if ($model->isDirty('site_id') && (int) $model->site_id !== $siteId) {
                throw new RuntimeException('Cannot reassign a site-owned record to a different site.');
            }
        });
    }

    /**
     * @return BelongsTo<Site, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
