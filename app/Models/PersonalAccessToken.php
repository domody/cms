<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

/**
 * Registered via Sanctum::usePersonalAccessTokenModel() so that resolving a
 * token's owner (tokenable()) bypasses TenantScope the same way login does -
 * the tenant isn't known until the owning User is found.
 */
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    public function tokenable()
    {
        return parent::tokenable()->withoutGlobalScope(TenantScope::class);
    }
}
