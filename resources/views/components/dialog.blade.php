@props(['title', 'close', 'size' => 'md'])

{{--
    Dialog shell (overlay, panel, header, close button). It holds no open/closed state:
    close is the Alpine expression that closes it ("open = false" in x-modal, "$wire.closeLibrary()"
    in a Livewire component). Extra attributes (x-show, x-cloak) land on the overlay.
--}}
<div
    {{ $attributes->class(['fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4']) }}
    x-on:click.self="{{ $close }}"
    x-on:keydown.escape.window="{{ $close }}"
    role="dialog"
    aria-modal="true"
    aria-label="{{ $title }}"
>
    <div @class([
        'flex max-h-full w-full flex-col overflow-hidden rounded-xl bg-background shadow-xl ring-1 ring-border',
        'max-w-md' => $size === 'md',
        'max-w-3xl' => $size === 'lg',
    ])>
        <header class="flex items-center justify-between gap-3 border-b px-5 py-3">
            <h2 class="text-sm font-semibold">{{ $title }}</h2>
            <x-icon-button icon="cancel-01" label="Close" x-on:click="{{ $close }}" />
        </header>
        <div class="overflow-y-auto p-5">
            {{ $slot }}
        </div>
    </div>
</div>
