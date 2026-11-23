<?php

use App\Models\Toast;
use App\Models\ToastPosition;

if (! function_exists('toast')) {
    /**
     * Flash a toast to show on the next page load (use before a redirect).
     * In Livewire components use the Toasts trait instead, unless redirecting.
     */
    function toast(string $type, string $message, ?string $title = null, string $position = ToastPosition::TOP_RIGHT): void
    {
        $toasts = session('toasts', []);
        $toasts[] = Toast::payload($type, $message, $title, $position);

        session()->flash('toasts', $toasts);
    }
}
