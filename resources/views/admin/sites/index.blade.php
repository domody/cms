@extends('layouts.admin', [
    'title' => 'Sites',
    'pageTitle' => 'Sites',
    'pageDescription' => 'Every site in the CMS.',
    'breadcrumbs' => [
        ['label' => 'Admin'],
        ['label' => 'Sites'],
    ],
])

@section('pageActions')
    <x-button icon="add-01" :href="route('admin.sites.create')">Add site</x-button>
@endsection

@section('content')
    <x-table>
        <x-slot:head>
            <th>Site</th>
            <th>Owner</th>
            <th>Plan</th>
        </x-slot:head>

        @forelse ($sites as $site)
            <tr data-href="{{ route('admin.sites.show', $site->slug) }}" class="cursor-pointer hover:bg-muted">
                <td>
                    <a href="{{ route('admin.sites.show', $site->slug) }}" class="font-medium">{{ $site->name }}</a>
                    <span class="ms-2 font-mono text-xs text-muted-foreground">/{{ $site->slug }}</span>
                </td>
                <td class="text-secondary-foreground">{{ $site->owners->pluck('name')->join(', ') ?: '—' }}</td>
                <td>{!! \App\Models\SitePlan::badge($site->plan) !!}</td>
            </tr>
        @empty
            <tr>
                <td colspan="3" class="empty">No sites yet.</td>
            </tr>
        @endforelse
    </x-table>
@endsection

@push('scripts')
    <script src="{{ asset('js/admin.sites.index.js') }}"></script>
@endpush
