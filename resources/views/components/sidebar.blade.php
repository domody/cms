@props(['menu'])

<aside {{ $attributes->class(['lg:w-64 lg:shrink-0 lg:min-h-screen lg:overflow-y-auto bg-gray-900 text-gray-100']) }}>
    <nav class="px-2 py-4 space-y-1">
        @foreach ($menu as $item)
            @if ($item['type'] === 'link')
                <a
                    href="{{ $item['url'] }}"
                    class="block rounded px-3 py-2 text-sm font-medium {{ request()->routeIs($item['route']) ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}"
                >
                    {{ $item['text'] }}
                </a>
            @elseif ($item['type'] === 'group')
                @php $groupIsActive = collect($item['links'])->contains(fn ($link) => request()->routeIs($link['route'])); @endphp
                <div class="sidebar-menu-group" data-sidebar-group>
                    <button
                        type="button"
                        data-sidebar-group-toggle
                        aria-expanded="{{ $groupIsActive ? 'true' : 'false' }}"
                        class="flex w-full items-center justify-between rounded px-3 py-2 text-sm font-medium text-gray-300 hover:bg-gray-800 hover:text-white"
                    >
                        <span>{{ $item['text'] }}</span>
                        <span data-sidebar-group-indicator class="shrink-0 text-xs text-gray-400" aria-hidden="true">{{ $groupIsActive ? '−' : '+' }}</span>
                    </button>
                    <div data-sidebar-group-panel class="ps-3 space-y-1 {{ $groupIsActive ? '' : 'hidden' }}">
                        @foreach ($item['links'] as $link)
                            <a
                                href="{{ $link['url'] }}"
                                class="block rounded px-3 py-2 text-sm font-medium {{ request()->routeIs($link['route']) ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}"
                            >
                                {{ $link['text'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    </nav>
</aside>
