@props(['tone' => 'neutral'])

{{-- Small status pill. Model constant classes (PageStatus::badge etc.) share the same .badge base. --}}
<span {{ $attributes->class([
    'badge',
    match ($tone) {
        'success' => 'bg-green-100 text-green-800',
        'danger' => 'bg-red-100 text-red-700',
        'warning' => 'bg-amber-100 text-amber-800',
        'info' => 'bg-blue-100 text-blue-800',
        default => 'bg-muted text-secondary-foreground ring-1 ring-border',
    },
]) }}>{{ $slot }}</span>
