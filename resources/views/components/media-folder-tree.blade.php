@props(['nodes', 'current' => null, 'openIds'])

<ul class="space-y-0.5">
    @foreach ($nodes as $node)
        <li>
            @if ($node->children->isNotEmpty())
                <details class="group" {{ $openIds->contains($node->id) ? 'open' : '' }}>
                    <summary class="flex cursor-pointer list-none items-center [&::-webkit-details-marker]:hidden">
                        <span class="flex size-6 shrink-0 items-center justify-center text-secondary-foreground" aria-hidden="true">
                            <x-hugeicons-arrow-right-01 class="size-4 transition-transform group-open:rotate-90" />
                        </span>
                        <a href="{{ route('media.index', ['folder' => $node->id]) }}" class="nav-link min-w-0 flex-1" @if ($current?->id === $node->id) aria-current="page" @endif>
                            <x-hugeicons-folder-01 class="size-4 shrink-0" aria-hidden="true" />
                            <span class="truncate">{{ $node->name }}</span>
                            <span class="ml-auto text-xs text-secondary-foreground">{{ $node->total_media }}</span>
                        </a>
                    </summary>
                    <div class="ml-3 border-l pl-1">
                        <x-media-folder-tree :nodes="$node->children" :current="$current" :open-ids="$openIds" />
                    </div>
                </details>
            @else
                <div class="flex items-center">
                    <span class="size-6 shrink-0" aria-hidden="true"></span>
                    <a href="{{ route('media.index', ['folder' => $node->id]) }}" class="nav-link min-w-0 flex-1" @if ($current?->id === $node->id) aria-current="page" @endif>
                        <x-hugeicons-folder-01 class="size-4 shrink-0" aria-hidden="true" />
                        <span class="truncate">{{ $node->name }}</span>
                        <span class="ml-auto text-xs text-secondary-foreground">{{ $node->total_media }}</span>
                    </a>
                </div>
            @endif
        </li>
    @endforeach
</ul>
