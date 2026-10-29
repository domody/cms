<?php

namespace App\Models;

/**
 * A user's site-wide access level: which areas of the CMS they can reach.
 * What a user may do inside one site is SiteRole, on the site_user pivot.
 * The roles each level grants live in config/acl.php.
 */
class AccessLevel
{
    /**
     * The CMS operator: whole-CMS area, any site (DB value).
     *
     * @var string
     */
    const ADMIN = 'admin';

    /**
     * A customer: only the sites they belong to (DB value).
     *
     * @var string
     */
    const CUSTOMER = 'customer';

    private const LABELS = [
        self::ADMIN => 'Admin',
        self::CUSTOMER => 'Customer',
    ];

    /**
     * Every access level, most privileged first.
     *
     * @return array<int, string>
     */
    public static function all(): array
    {
        return array_keys(self::LABELS);
    }

    /**
     * Get the label for an access level.
     */
    public static function label(string $accessLevel): string
    {
        return self::LABELS[$accessLevel] ?? 'Unknown';
    }

    /**
     * Get badge class for an access level.
     */
    public static function getClass(string $accessLevel): string
    {
        return match ($accessLevel) {
            self::ADMIN => 'bg-purple-100 text-purple-800',
            self::CUSTOMER => 'bg-blue-100 text-blue-800',
            default => 'bg-gray-100 text-gray-700',
        };
    }

    /**
     * Get the badge HTML for an access level.
     */
    public static function badge(string $accessLevel): string
    {
        return sprintf(
            '<span class="rounded-full px-2 py-0.5 text-xs font-medium %s">%s</span>',
            self::getClass($accessLevel),
            self::label($accessLevel),
        );
    }
}
