<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Users are global: they are not site-owned. Which sites a user can work in
 * (and as what) is the site_user pivot, exposed through sites(). Separately,
 * access_level (admin or customer) decides which areas of the CMS they reach.
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Mirrors the column default, so a freshly created model is a customer.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'access_level' => AccessLevel::CUSTOMER,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'disabled_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->access_level === AccessLevel::ADMIN;
    }

    public function isDisabled(): bool
    {
        return $this->disabled_at !== null;
    }

    public function disable(): void
    {
        $this->forceFill(['disabled_at' => now()])->save();
    }

    public function enable(): void
    {
        $this->forceFill(['disabled_at' => null])->save();
    }

    /**
     * Whether the user's access level grants a controller action (config/acl.php).
     */
    public function canAccess(string $controller, string $method): bool
    {
        $level = $this->access_level ?? AccessLevel::CUSTOMER;

        return $this->rolesGrant(config("acl.accessLevels.{$level}", []), $controller, $method);
    }

    /**
     * Whether the action is also open to the given SiteRole (config/acl.php
     * `siteRoles`), on top of the user's access level. Platform admins are
     * not limited by site roles, so callers skip this for them.
     */
    public function canAccessAs(string $siteRole, string $controller, string $method): bool
    {
        return $this->canAccess($controller, $method)
            && $this->rolesGrant(config("acl.siteRoles.{$siteRole}", []), $controller, $method);
    }

    /**
     * Whether the user holds an ACL role (e.g. `siteManage`) on a site: any
     * platform admin does, a customer through their SiteRole.
     */
    public function hasSiteAccess(Site $site, string $aclRole): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return in_array($aclRole, config('acl.siteRoles.'.$this->roleOn($site), []), true);
    }

    /**
     * @param  array<int, string>  $roleNames
     */
    private function rolesGrant(array $roleNames, string $controller, string $method): bool
    {
        foreach ($roleNames as $role) {
            foreach (config("acl.roles.{$role}", []) as [$roleController, $roleMethod]) {
                if ($roleController === $controller && $roleMethod === $method) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Sites the user may enter: every site for an admin, otherwise the ones
     * they are a member of.
     *
     * @return Builder<Site>|BelongsToMany<Site, $this, SiteMembership>
     */
    public function accessibleSites(): Builder|BelongsToMany
    {
        return $this->isAdmin() ? Site::query() : $this->sites();
    }

    /**
     * Sites this user belongs to, in the order they joined them.
     *
     * @return BelongsToMany<Site, $this, SiteMembership>
     */
    public function sites(): BelongsToMany
    {
        return $this->belongsToMany(Site::class)
            ->using(SiteMembership::class)
            ->withPivot('role')
            ->withTimestamps()
            ->orderBy('site_user.id');
    }

    /**
     * The site a user lands on after logging in.
     */
    public function defaultSite(): ?Site
    {
        return $this->sites()->first();
    }

    /**
     * The user's SiteRole on a site, or null if they are not a member.
     */
    public function roleOn(Site $site): ?string
    {
        return $this->sites()->whereKey($site->id)->first()?->pivot->role;
    }
}
