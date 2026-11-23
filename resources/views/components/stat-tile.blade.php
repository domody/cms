@props(['label', 'value', 'icon' => null, 'href' => null])

{{-- One headline number (dashboards). Slot is the small line under it. With href the whole tile is a link. --}}
@php
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->class([
    'group flex flex-col gap-3 rounded-xl bg-background p-4 ring-1 ring-border',
    'transition hover:bg-muted hover:ring-foreground/20' => $href,
]) }}>
    <div class="flex items-center gap-2 text-sm text-secondary-foreground">
        @if ($icon)
            <x-dynamic-component :component="'hugeicons-'.$icon" class="size-4 shrink-0" aria-hidden="true" />
        @endif
        <span class="truncate">{{ $label }}</span>
        @if ($href)
            <x-hugeicons-arrow-right-01 class="ms-auto size-4 text-muted-foreground transition-transform group-hover:translate-x-0.5" aria-hidden="true" />
        @endif
    </div>
    <p class="text-2xl font-semibold tabular-nums tracking-tight">{{ $value }}</p>
    @if ($slot->isNotEmpty())
        <div class="text-xs text-secondary-foreground">{{ $slot }}</div>
    @endif
</{{ $tag }}>
