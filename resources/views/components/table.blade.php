{{--
    Usage: <x-table><x-slot:head><th>Title</th>...</x-slot:head> <tr>...</tr> </x-table>
    Cell spacing comes from .data-table; an empty-state cell uses <td colspan="n" class="empty">.
--}}
<div {{ $attributes->class(['overflow-x-auto rounded-xl ring-1 ring-border']) }}>
    <table class="data-table">
        @isset($head)
            <thead>
                <tr>{{ $head }}</tr>
            </thead>
        @endisset
        <tbody>
            {{ $slot }}
        </tbody>
    </table>
</div>
