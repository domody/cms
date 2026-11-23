@props(['icon' => null, 'title' => null])

{{-- "Nothing here yet" panel. Slot is the explanation; actions slot holds a call to action. --}}
<div {{ $attributes->class(['flex flex-col items-center gap-2 rounded-xl border border-dashed px-6 py-10 text-center']) }}>
    @if ($icon)
        <x-dynamic-component :component="'hugeicons-'.$icon" class="size-8 text-muted-foreground" aria-hidden="true" />
    @endif
    @if ($title)
        <p class="text-sm font-semibold">{{ $title }}</p>
    @endif
    <div class="max-w-sm text-sm text-secondary-foreground">{{ $slot }}</div>
    @isset($actions)
        <div class="mt-2 flex items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
