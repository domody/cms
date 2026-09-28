<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/**
 * Marks a model as tenant-owned: every query is automatically scoped to
 * the authenticated user's tenant (see TenantScope), new records are
 * stamped with it, and it cannot be reassigned to a different tenant.
 * Any model storing tenant-specific data should use this trait instead
 * of filtering by tenant_id manually in queries.
 */
trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model): void {
            // Authenticated: the tenant is never taken from client input,
            // it's always the current user's own - mass assignment can't
            // be used to create a record under a different tenant.
            // Unauthenticated (console, seeders, queue jobs): the caller
            // must set tenant_id explicitly, since there is no "current"
            // tenant to infer it from.
            if ($user = auth()->user()) {
                $model->tenant_id = $user->tenant_id;
            }
        });

        static::updating(function ($model): void {
            $user = auth()->user();

            if ($user === null) {
                return;
            }

            if ($model->isDirty('tenant_id') && (int) $model->tenant_id !== (int) $user->tenant_id) {
                throw new RuntimeException('Cannot reassign a tenant-owned record to a different tenant.');
            }
        });
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
