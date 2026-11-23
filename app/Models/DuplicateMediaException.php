<?php

namespace App\Models;

use RuntimeException;

/**
 * Thrown when an uploaded file is byte-for-byte identical to an image the
 * site already has. Carries that image, so a caller can use it instead.
 */
class DuplicateMediaException extends RuntimeException
{
    public function __construct(public readonly Media $existing)
    {
        parent::__construct("This image is already in the library as \"{$existing->original_name}\".");
    }
}
