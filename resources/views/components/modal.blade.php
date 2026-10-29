@props(['title', 'open' => false])

{{-- Usage: <x-modal title="..."><x-slot:trigger>...button...</x-slot:trigger> body </x-modal>. Pass :open="true" to start open (e.g. after a validation error). --}}
<div x-data="{ open: @js($open) }" x-on:keydown.escape.window="open = false" class="contents">
    <span x-on:click="open = true" class="contents">{{ $trigger }}</span>

    <div x-show="open" x-cloak x-on:click.self="open = false" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" role="dialog" aria-modal="true" aria-label="{{ $title }}">
        <div class="w-full max-w-md rounded-lg bg-background p-5 shadow-lg">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-sm font-semibold">{{ $title }}</h2>
                <button type="button" x-on:click="open = false" class="btn-icon" aria-label="Close">
                    <x-hugeicons-cancel-01 />
                </button>
            </div>
            {{ $slot }}
        </div>
    </div>
</div>
