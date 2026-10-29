<?php

namespace App\Models;

/**
 * A toast notification payload. Not persisted: it is either dispatched as a
 * browser event (Livewire) or flashed to the session (controllers).
 */
class Toast
{
    /**
     * Build the validated payload shared by the `toast()` helper and the Livewire trait.
     *
     * @return array{type: string, title: string|null, message: string, position: string}
     */
    public static function payload(
        string $type,
        string $message,
        ?string $title = null,
        string $position = ToastPosition::TOP_RIGHT,
    ): array {
        return [
            'type' => ToastType::normalize($type),
            'title' => $title,
            'message' => $message,
            'position' => ToastPosition::normalize($position),
        ];
    }
}
