@props(['tone' => 'info', 'title' => null])

{{-- Inline notice. tone: info | warning | danger. --}}
<div role="{{ $tone === 'danger' ? 'alert' : 'status' }}" {{ $attributes->class([
    'flex gap-3 rounded-xl p-4 text-sm',
    match ($tone) {
        'warning' => 'bg-amber-50 text-amber-900 ring-1 ring-amber-200',
        'danger' => 'bg-red-50 text-red-900 ring-1 ring-red-200',
        default => 'bg-muted text-foreground ring-1 ring-border',
    },
]) }}>
    @if ($tone === 'info')
        <x-hugeicons-information-circle class="size-5 shrink-0 opacity-70" aria-hidden="true" />
    @else
        <x-hugeicons-alert-02 class="size-5 shrink-0 opacity-70" aria-hidden="true" />
    @endif
    <div class="min-w-0 flex-1 space-y-1">
        @if ($title)
            <p class="font-semibold">{{ $title }}</p>
        @endif
        <div class="opacity-90">{{ $slot }}</div>
    </div>
</div>
