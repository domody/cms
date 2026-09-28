<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Restricts every query on a tenant-owned model to the authenticated
 * user's tenant. Applied automatically by the BelongsToTenant trait,
 * never opted into per-query, so no caller can forget to filter.
 *
 * Fails closed with no authenticated user: a query returns nothing rather
 * than every tenant's rows. This applies to User too - login, password
 * reset, and Sanctum token lookup all resolve a user before any tenant is
 * known, but that's handled by bypassing this scope entirely for those
 * specific queries (see TenantUnscopedEloquentUserProvider and
 * App\Models\PersonalAccessToken), not by exempting User here. Any other
 * query against User - a route, a job, anything unauthenticated - gets the
 * same fail-closed treatment as every other tenant-owned model.
 *
 * Calling auth()->user() below (rather than the non-resolving hasUser())
 * is safe now specifically because of that bypass: resolving the guard's
 * user, when not yet cached, queries User through the tenant-unscoped
 * provider, which never re-enters this scope. Before that provider existed,
 * this line was the exact cause of an infinite loop (see TenantScopingTest).
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = auth()->user();

        if ($user === null) {
            $builder->whereRaw('1 = 0');

            return;
        }

        $builder->where($model->qualifyColumn('tenant_id'), $user->tenant_id);
    }
}
