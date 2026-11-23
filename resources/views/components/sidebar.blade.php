@props(['menu', 'currentSite' => null, 'sites' => collect()])

<aside {{ $attributes->class(['flex flex-col lg:w-64 lg:shrink-0 lg:min-h-screen lg:overflow-y-auto']) }}>
    {{-- top: site switcher --}}
    <div class="flex h-16 items-center border-b px-2">
        <x-site-switcher class="w-full" :current-site="$currentSite" :sites="$sites" />
    </div>
    {{-- mid --}}
    <nav class="px-2 py-4 space-y-1">
        @foreach ($menu as $item)
            @if ($item['type'] === 'link')
                <a
                    href="{{ $item['url'] }}"
                    class="sidebar-link"
                    @if ($item['active'] ?? request()->routeIs($item['route'])) aria-current="page" @endif
                >
                    @isset($item['icon'])
                        <x-dynamic-component :component="$item['icon']" class="size-5 shrink-0" aria-hidden="true" />
                    @endisset
                    {{ $item['text'] }}
                </a>
            @elseif ($item['type'] === 'group')
                @php $groupIsActive = collect($item['links'])->contains(fn ($link) => request()->routeIs($link['route'])); @endphp
                <div class="sidebar-menu-group" data-sidebar-group>
                    <button
                        type="button"
                        data-sidebar-group-toggle
                        aria-expanded="{{ $groupIsActive ? 'true' : 'false' }}"
                        class="sidebar-link w-full justify-between"
                    >
                        <span class="flex items-center gap-2">
                            @isset($item['icon'])
                                <x-dynamic-component :component="$item['icon']" class="size-5 shrink-0" aria-hidden="true" />
                            @endisset
                            {{ $item['text'] }}
                        </span>
                        <span data-sidebar-group-indicator class="shrink-0 text-xs text-muted-foreground" aria-hidden="true">{{ $groupIsActive ? '−' : '+' }}</span>
                    </button>
                    <div data-sidebar-group-panel class="ps-3 space-y-1 {{ $groupIsActive ? '' : 'hidden' }}">
                        @foreach ($item['links'] as $link)
                            <a
                                href="{{ $link['url'] }}"
                                class="sidebar-link" @if (request()->routeIs($link['route'])) aria-current="page" @endif
                            >
                                @isset($link['icon'])
                                    <x-dynamic-component :component="$link['icon']" class="size-5 shrink-0" aria-hidden="true" />
                                @endisset
                                {{ $link['text'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    </nav>
    {{-- bottom: user menu --}}
    <div class="mt-auto border-t px-2 py-2">
        <x-user-menu class="w-full" />
    </div>
</aside>
