<?php

namespace App\Auth;

use App\Models\Scopes\TenantScope;
use Illuminate\Auth\EloquentUserProvider;

/**
 * Identical to Laravel's default Eloquent provider, except every query it
 * builds has TenantScope removed. Login, password reset, and "remember me"
 * all resolve a user before any tenant is known - that's what these queries
 * are for - so they're the only place the scope is allowed to be bypassed.
 * Every other query against User (a route, a job, anything else) keeps the
 * scope and fails closed like any other tenant-owned model.
 */
class TenantUnscopedEloquentUserProvider extends EloquentUserProvider
{
    protected function newModelQuery($model = null)
    {
        return parent::newModelQuery($model)->withoutGlobalScope(TenantScope::class);
    }
}
