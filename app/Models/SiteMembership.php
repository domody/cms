<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * One row of the site_user pivot: a user's membership of a site, and their
 * role there (a SiteRole value).
 */
class SiteMembership extends Pivot
{
    protected $table = 'site_user';

    public $incrementing = true;
}
