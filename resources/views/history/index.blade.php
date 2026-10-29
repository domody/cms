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
    <form method="GET" class="mb-4 flex flex-wrap items-center gap-3">
        <select name="type" class="input w-auto">
            <option value="">All types</option>
            @foreach (\App\Models\ActivityLog::TYPES as $value => $label)
                <option value="{{ $value }}" @selected(($filters['type'] ?? null) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="user" class="input w-auto">
            <option value="">Everyone</option>
            @foreach ($people as $id => $name)
                <option value="{{ $id }}" @selected((int) ($filters['user'] ?? 0) === $id)>{{ $name }}</option>
            @endforeach
        </select>
        @if (! empty($filters['subject']))
            <input type="hidden" name="subject" value="{{ $filters['subject'] }}">
            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-700">One record only</span>
        @endif
        <button type="submit" class="btn">Filter</button>
        @if ($filters)
            <a href="{{ route('history.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Clear</a>
        @endif
    </form>

    <div class="overflow-hidden rounded-lg border">
        <table class="min-w-full divide-y divide-border text-sm">
            <thead class="bg-muted text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                <tr>
                    <th class="px-4 py-3">When</th>
                    <th class="px-4 py-3">Who</th>
                    <th class="px-4 py-3">What</th>
                    <th class="px-4 py-3">Changes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse ($logs as $log)
                    <tr class="align-top">
                        <td class="whitespace-nowrap px-4 py-3 text-secondary-foreground" title="{{ $log->created_at->toDateTimeString() }}">
                            {{ $log->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-4 py-3">
                            {{ $log->actor() }}
                            @if ($log->source !== 'web') <span class="ms-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">{{ $log->source }}</span> @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="font-medium">{{ \Illuminate\Support\Str::headline($log->event) }}</span>
                            {{ strtolower(\App\Models\ActivityLog::typeLabel($log->subject_type)) }}
                            <span class="font-medium">{{ $log->subject_label }}</span>
                        </td>
                        <td class="px-4 py-3">
                            @foreach ($log->changes ?? [] as $field => $change)
                                <div class="break-words">
                                    <strong>{{ $fieldName($field) }}:</strong>
                                    <span class="text-red-600">{{ $change['old'] ?? 'Empty' }}</span> →
                                    <span class="text-green-700">{{ $change['new'] ?? 'Empty' }}</span>
                                </div>
                            @endforeach
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-secondary-foreground">Nothing recorded yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="py-4">{{ $logs->links() }}</div>
@endsection
