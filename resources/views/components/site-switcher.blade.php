@props(['currentSite' => null, 'sites' => collect()])

<div {{ $attributes->class(['relative']) }} data-site-switcher>
    <button
        type="button"
        data-site-switcher-toggle
        aria-haspopup="true"
        aria-expanded="false"
        class="flex w-full items-center justify-between gap-2 rounded px-3 py-2 text-sm font-medium hover:bg-background/80"
    >
        <span class="truncate">{{ $currentSite?->name ?? 'Admin' }}</span>
        <x-hugeicons-unfold-more class="size-4 shrink-0 text-secondary-foreground" aria-hidden="true" />
    </button>
    <div
        data-site-switcher-panel
        class="fixed z-50 hidden w-56 rounded-lg border bg-background p-1 shadow-lg"
    >
        @forelse ($sites as $site)
            <a
                href="{{ route('dashboard', ['site' => $site->slug]) }}"
                class="block truncate rounded px-3 py-2 text-sm hover:bg-muted {{ $currentSite?->is($site) ? 'bg-muted font-medium' : '' }}"
            >
                {{ $site->name }}
            </a>
        @empty
            <x-empty-state figure="router" title="No sites yet">
                You'll see sites here once you're added to one.
            </x-empty-state>
        @endforelse
    </div>
</div>

@once
    @push('scripts')
        <script src="{{ asset('js/components.site-switcher.js') }}"></script>
    @endpush
@endonce
