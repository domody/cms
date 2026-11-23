<?php

namespace App\Livewire\Concerns;

use App\Models\Toast;
use App\Models\ToastPosition;

/**
 * Show a toast from a Livewire component.
 *
 * This dispatches a browser event, so it is lost if the component then
 * redirects. Before a `$this->redirect(...)`, call the global `toast()` helper
 * instead, which flashes the toast to the session for the next page load.
 */
trait Toasts
{
    public function toast(string $type, string $message, ?string $title = null, string $position = ToastPosition::TOP_RIGHT): void
    {
        $this->dispatch('toast', ...Toast::payload($type, $message, $title, $position));
    }
}
