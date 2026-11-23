@props(['icon' => null, 'title' => null, 'figure' => null])

{{-- "Nothing here yet" panel. Slot is the explanation; actions slot holds a call to action. `figure` is a Hairline figure name and replaces the icon. --}}
<div {{ $attributes->class(['flex flex-col items-center gap-0 px-6 py-10 text-center']) }}>
    @if ($figure)
        <div class="w-56" data-hairline="{{ $figure }}"></div>
    @elseif ($icon)
        <x-dynamic-component :component="'hugeicons-'.$icon" class="size-8 text-muted-foreground" aria-hidden="true" />
    @endif
    @if ($title)
        <p class="text-sm font-semibold">{{ $title }}</p>
    @endif
    <div class="max-w-sm text-sm text-secondary-foreground">{{ $slot }}</div>
    @isset($actions)
        <div class="mt-4 flex items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
