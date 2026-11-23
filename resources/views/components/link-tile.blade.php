@props(['href', 'icon', 'title'])

{{-- A tile that opens something (a collection, a site). Slot is the one-line detail under the title. --}}
<a href="{{ $href }}" {{ $attributes->class(['group flex items-center gap-3 rounded-xl p-4 ring-1 ring-border transition hover:bg-muted hover:ring-foreground/20']) }}>
    <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-muted text-secondary-foreground group-hover:bg-background">
        <x-dynamic-component :component="'hugeicons-'.$icon" class="size-5" aria-hidden="true" />
    </span>
    <span class="min-w-0">
        <span class="block truncate text-sm font-medium">{{ $title }}</span>
        <span class="block text-xs text-secondary-foreground">{{ $slot }}</span>
    </span>
    <x-hugeicons-arrow-right-01 class="ms-auto size-4 shrink-0 text-muted-foreground transition-transform group-hover:translate-x-0.5" aria-hidden="true" />
</a>
