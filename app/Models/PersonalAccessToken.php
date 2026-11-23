<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

/**
 * Registered via Sanctum::usePersonalAccessTokenModel(). Adds the site a
 * token is bound to: an API token always works inside exactly one site.
 */
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    /**
     * @return BelongsTo<Site, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
