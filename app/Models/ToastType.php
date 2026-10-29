<?php

namespace App\Models;

use InvalidArgumentException;

/**
 * Kind of toast notification. Drives its icon, colour and whether it auto-dismisses.
 */
class ToastType
{
    /**
     * Something worked (UI value).
     *
     * @var string
     */
    const SUCCESS = 'success';

    /**
     * Something failed. Stays on screen until dismissed (UI value).
     *
     * @var string
     */
    const ERROR = 'error';

    /**
     * Something needs attention (UI value).
     *
     * @var string
     */
    const WARNING = 'warning';

    /**
     * Neutral information (UI value).
     *
     * @var string
     */
    const INFO = 'info';

    /**
     * Every toast type.
     *
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [self::SUCCESS, self::ERROR, self::WARNING, self::INFO];
    }

    /**
     * Return the type if it is known, otherwise fail loudly.
     *
     * @throws InvalidArgumentException
     */
    public static function normalize(string $type): string
    {
        if (! in_array($type, self::all(), true)) {
            throw new InvalidArgumentException("Unknown toast type [$type].");
        }

        return $type;
    }
}
