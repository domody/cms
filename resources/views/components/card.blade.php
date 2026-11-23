@props(['title' => null, 'description' => null, 'tone' => null, 'flush' => false])

{{--
    The one surface used to group content. A page should only ever nest one level of these.
    flush: the body has no padding and the header gets a divider (for tables and lists).
    Slots: actions (header, right), footer.
--}}
@php
    $hasHeader = $title !== null || isset($actions);
@endphp

<section {{ $attributes->class(['rounded-xl bg-background ring-1', 'ring-border' => $tone !== 'danger', 'ring-danger/30' => $tone === 'danger']) }}>
    @if ($hasHeader)
        <header @class(['flex items-start gap-3 px-5 pt-4', 'border-b pb-3' => $flush])>
            <div class="min-w-0 flex-1">
                @if ($title !== null)
                    <h2 @class(['text-sm font-semibold', 'text-danger' => $tone === 'danger'])>{{ $title }}</h2>
                @endif
                @if ($description)
                    <p class="mt-0.5 text-sm text-secondary-foreground">{{ $description }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex shrink-0 items-center gap-1">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    <div @class(['p-5' => ! $flush, 'pt-4' => ! $flush && $hasHeader])>
        {{ $slot }}
    </div>

    @isset($footer)
        <footer class="border-t px-5 py-3 text-xs text-secondary-foreground">{{ $footer }}</footer>
    @endisset
</section>
