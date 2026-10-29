<?php

namespace App\Models;

/**
 * Publication state of a page. Only published pages are served by the API.
 */
class PageStatus
{
    /**
     * Not yet live, hidden from the API (DB value).
     *
     * @var string
     */
    const DRAFT = 'draft';

    /**
     * Live, served by the API (DB value).
     *
     * @var string
     */
    const PUBLISHED = 'published';

    private const LABELS = [
        self::DRAFT => 'Draft',
        self::PUBLISHED => 'Published',
    ];

    /**
     * Every page status, in editing order.
     *
     * @return array<int, string>
     */
    public static function all(): array
    {
        return array_keys(self::LABELS);
    }

    /**
     * Status => label pairs, for dropdowns.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return self::LABELS;
    }

    /**
     * Get the label for a status.
     */
    public static function label(string $status): string
    {
        return self::LABELS[$status] ?? 'Unknown';
    }

    /**
     * Get badge class for a status.
     */
    public static function getClass(string $status): string
    {
        return match ($status) {
            self::PUBLISHED => 'bg-green-100 text-green-800',
            self::DRAFT => 'bg-gray-100 text-gray-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }

    /**
     * Get the badge HTML for a status.
     */
    public static function badge(string $status): string
    {
        return sprintf(
            '<span class="rounded-full px-2 py-0.5 text-xs font-medium %s">%s</span>',
            self::getClass($status),
            self::label($status),
        );
    }
}
