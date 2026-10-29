<?php

namespace App\Livewire;

use App\Livewire\Concerns\Toasts;
use Illuminate\View\View;
use Livewire\Component;

/**
 * TEMP: buttons that fire every toast variant, for styling the toast component.
 * Remove together with resources/views/livewire/toast-preview.blade.php and its dashboard usage.
 */
class ToastPreview extends Component
{
    use Toasts;

    public function render(): View
    {
        return view('livewire.toast-preview');
    }
}
