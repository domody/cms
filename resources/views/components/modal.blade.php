@props(['title', 'open' => false])

{{-- Usage: <x-modal title="..."><x-slot:trigger>...button...</x-slot:trigger> body </x-modal>. Pass :open="true" to start open (e.g. after a validation error). --}}
<div x-data="{ open: @js($open) }" class="contents">
    <span x-on:click="open = true" class="contents">{{ $trigger }}</span>

    <x-dialog :title="$title" close="open = false" x-show="open" x-cloak>
        {{ $slot }}
    </x-dialog>
</div>
