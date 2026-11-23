@props(['used', 'limit' => null])

{{-- Media storage used against the plan's limit (null = no limit, no bar). Turns red from 90%. --}}
@php
    $percent = $limit ? min(100, round($used / $limit * 100)) : null;
@endphp

<div {{ $attributes->class(['text-xs text-secondary-foreground']) }}>
    <div class="flex justify-between gap-2">
        @if ($limit)
            <span>{{ \App\Models\Media::formatBytes($used) }} used of {{ \App\Models\Media::formatBytes($limit) }}</span>
        @else
            <span>{{ \App\Models\Media::formatBytes($used) }} used</span>
            <span>No limit</span>
        @endif
    </div>
    @if ($percent !== null)
        <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-muted" role="meter" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100" aria-label="Storage used">
            <div class="h-full {{ $percent >= 90 ? 'bg-danger' : 'bg-foreground' }}" style="width: {{ $percent }}%"></div>
        </div>
    @endif
</div>
