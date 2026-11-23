@extends('layouts.admin', [
    'title' => 'History',
    'pageTitle' => 'History',
    'pageDescription' => 'Who changed what on this site, and when.',
    'breadcrumbs' => [
        ['label' => 'History'],
    ],
])

@php
    $fieldName = fn (string $field) => str_replace('content.', '', $field);
@endphp

@section('content')
    <form method="GET" class="mb-4 flex flex-wrap items-center gap-2">
        <select name="type" aria-label="Type" class="input h-8 w-auto py-1">
            <option value="">All types</option>
            @foreach (\App\Models\ActivityLog::TYPES as $value => $label)
                <option value="{{ $value }}" @selected(($filters['type'] ?? null) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="user" aria-label="Person" class="input h-8 w-auto py-1">
            <option value="">Everyone</option>
            @foreach ($people as $id => $name)
                <option value="{{ $id }}" @selected((int) ($filters['user'] ?? 0) === $id)>{{ $name }}</option>
            @endforeach
        </select>
        @if (! empty($filters['subject']))
            <input type="hidden" name="subject" value="{{ $filters['subject'] }}">
            <x-badge>One record only</x-badge>
        @endif
        <x-button type="submit" variant="outline" icon="filter">Filter</x-button>
        @if ($filters)
            <x-button variant="subtle" :href="route('history.index')">Clear</x-button>
        @endif
    </form>

    <x-table>
        <x-slot:head>
            <th>When</th>
            <th>Who</th>
            <th>What</th>
            <th>Changes</th>
        </x-slot:head>

        @forelse ($logs as $log)
            <tr class="align-top [&>td]:align-top">
                <td class="whitespace-nowrap text-secondary-foreground" title="{{ $log->created_at->toDateTimeString() }}">
                    {{ $log->created_at->format('d/m/Y H:i') }}
                </td>
                <td class="whitespace-nowrap">
                    {{ $log->actor() }}
                    @if ($log->source !== 'web') <x-badge class="ms-1">{{ $log->source }}</x-badge> @endif
                </td>
                <td>
                    <span class="font-medium">{{ \Illuminate\Support\Str::headline($log->event) }}</span>
                    <span class="text-secondary-foreground">{{ strtolower(\App\Models\ActivityLog::typeLabel($log->subject_type)) }}</span>
                    <span class="font-medium">{{ $log->subject_label }}</span>
                </td>
                <td class="space-y-1">
                    @foreach ($log->changes ?? [] as $field => $change)
                        {{-- Long values (repeater JSON) are clamped; click to read the whole change. --}}
                        <div x-data="{ expanded: false }" x-on:click="expanded = ! expanded" x-bind:class="expanded || 'line-clamp-3'" class="cursor-pointer break-words" title="Click to expand or collapse">
                            <span class="text-secondary-foreground">{{ $fieldName($field) }}:</span>
                            <span class="rounded bg-red-50 px-1 text-red-700 line-through decoration-red-300">{{ $change['old'] ?? 'Empty' }}</span>
                            <span class="text-muted-foreground" aria-label="changed to">→</span>
                            <span class="rounded bg-green-50 px-1 text-green-800">{{ $change['new'] ?? 'Empty' }}</span>
                        </div>
                    @endforeach
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="empty">Nothing recorded yet.</td>
            </tr>
        @endforelse
    </x-table>

    <div class="py-4">{{ $logs->links() }}</div>
@endsection
