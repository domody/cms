<?php

namespace App\Models;

/**
 * A user's role on one site, stored on the site_user pivot. The same user
 * can hold a different role on each site they belong to.
 */
class SiteRole
{
    /**
     * Owner of the site (DB value).
     *
     * @var string
     */
    const OWNER = 'owner';

    /**
     * Site administrator (DB value).
     *
     * @var string
     */
    const ADMIN = 'admin';

    /**
     * Content editor (DB value).
     *
     * @var string
     */
    const EDITOR = 'editor';

    private const LABELS = [
        self::OWNER => 'Owner',
        self::ADMIN => 'Admin',
        self::EDITOR => 'Editor',
    ];

    /**
     * Every site role, most privileged first.
     *
     * @return array<int, string>
     */
    public static function all(): array
    {
        return array_keys(self::LABELS);
    }

    /**
     * Get the label for a site role.
     */
    public static function label(string $role): string
    {
        return self::LABELS[$role] ?? 'Unknown';
    }

    /**
     * Get badge class for a site role.
     */
    public static function getClass(string $role): string
    {
        return match ($role) {
            self::OWNER => 'bg-purple-100 text-purple-800',
            self::ADMIN => 'bg-blue-100 text-blue-800',
            self::EDITOR => 'bg-gray-100 text-gray-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }

    /**
     * Get the badge HTML for a site role.
     */
    public static function badge(string $role): string
    {
        return sprintf(
            '<span class="rounded-full px-2 py-0.5 text-xs font-medium %s">%s</span>',
            self::getClass($role),
            self::label($role),
        );
    }
}
