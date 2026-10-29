<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Restricts every query on a site-owned model to the site the request is
 * currently inside (see CurrentSite). Applied automatically by the
 * BelongsToSite trait, never opted into per-query, so no caller can forget
 * to filter.
 *
 * Fails closed and loudly with no current site: the query throws
 * NoCurrentSiteException rather than returning every site's rows (or
 * silently returning none, which would look like "this site has no data").
 * The scope deliberately does not look at the authenticated user, so it can
 * never re-enter the auth guard; user-to-site membership is verified once,
 * by SetCurrentSite, before the site is set.
 */
class SiteScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $siteId = app(CurrentSite::class)->id();

        if ($siteId === null) {
            throw NoCurrentSiteException::forModel($model::class);
        }

        $builder->where($model->qualifyColumn('site_id'), $siteId);
    }
}
