<?php

namespace App\Models;

/**
 * The plan a site is on (sites.plan).
 */
class SitePlan
{
    /**
     * Entry plan (DB value).
     *
     * @var string
     */
    const STARTER = 'starter';

    /**
     * Standard plan (DB value).
     *
     * @var string
     */
    const STANDARD = 'standard';

    /**
     * Growth plan (DB value).
     *
     * @var string
     */
    const GROWTH = 'growth';

    /**
     * Custom-arranged plan (DB value).
     *
     * @var string
     */
    const BESPOKE = 'bespoke';

    private const LABELS = [
        self::STARTER => 'Starter',
        self::STANDARD => 'Standard',
        self::GROWTH => 'Growth',
        self::BESPOKE => 'Bespoke',
    ];

    /**
     * Every plan, smallest first.
     *
     * @return array<int, string>
     */
    public static function all(): array
    {
        return array_keys(self::LABELS);
    }

    /**
     * Media storage the plan includes, in bytes (originals and resized
     * copies); null means no limit.
     */
    public static function storageLimit(string $plan): ?int
    {
        return match ($plan) {
            self::STARTER => 1 * 1024 ** 3,
            self::STANDARD => 5 * 1024 ** 3,
            self::GROWTH => 25 * 1024 ** 3,
            default => null,
        };
    }

    /**
     * Get the label for a plan.
     */
    public static function label(string $plan): string
    {
        return self::LABELS[$plan] ?? 'Unknown';
    }

    /**
     * Get badge class for a plan.
     */
    public static function getClass(string $plan): string
    {
        return match ($plan) {
            self::STARTER => 'bg-gray-100 text-gray-700',
            self::STANDARD => 'bg-info/10 text-info ring ring-info/25',
            self::GROWTH => 'bg-success/10 text-success ring ring-success/25',
            self::BESPOKE => 'bg-purple-100 text-purple-800',
            default => 'bg-gray-100 text-gray-700',
        };
    }

    /**
     * Get the badge HTML for a plan.
     */
    public static function badge(string $plan): string
    {
        return sprintf(
            '<span class="badge %s">%s</span>',
            self::getClass($plan),
            self::label($plan),
        );
    }
}
