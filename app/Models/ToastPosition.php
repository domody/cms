<?php

namespace App\Models;

use InvalidArgumentException;

/**
 * Screen corner a toast appears in.
 */
class ToastPosition
{
    /**
     * Top-left corner (UI value).
     *
     * @var string
     */
    const TOP_LEFT = 'top-left';

    /**
     * Top-right corner, the default (UI value).
     *
     * @var string
     */
    const TOP_RIGHT = 'top-right';

    /**
     * Bottom-left corner (UI value).
     *
     * @var string
     */
    const BOTTOM_LEFT = 'bottom-left';

    /**
     * Bottom-right corner (UI value).
     *
     * @var string
     */
    const BOTTOM_RIGHT = 'bottom-right';

    /**
     * Every toast position.
     *
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [self::TOP_LEFT, self::TOP_RIGHT, self::BOTTOM_LEFT, self::BOTTOM_RIGHT];
    }

    /**
     * Return the position if it is known, otherwise fail loudly.
     *
     * @throws InvalidArgumentException
     */
    public static function normalize(string $position): string
    {
        if (! in_array($position, self::all(), true)) {
            throw new InvalidArgumentException("Unknown toast position [$position].");
        }

        return $position;
    }

    /**
     * Get the container placement classes for a position (full width on mobile).
     */
    public static function getClass(string $position): string
    {
        return match ($position) {
            self::TOP_LEFT => 'top-0 inset-x-0 sm:right-auto sm:left-0',
            self::BOTTOM_LEFT => 'bottom-0 inset-x-0 sm:right-auto sm:left-0',
            self::BOTTOM_RIGHT => 'bottom-0 inset-x-0 sm:left-auto sm:right-0',
            default => 'top-0 inset-x-0 sm:left-auto sm:right-0',
        };
    }
}
