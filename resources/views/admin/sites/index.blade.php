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
    <a href="{{ route('admin.sites.create') }}" class="btn">
        <x-hugeicons-add-01 aria-hidden="true" />
        Add site
    </a>
@endsection

@section('content')
    <div class="overflow-hidden rounded-lg border">
        <table class="min-w-full divide-y divide-border text-sm">
            <thead class="bg-muted text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                <tr>
                    <th class="px-4 py-3">Site</th>
                    <th class="px-4 py-3">Owner</th>
                    <th class="px-4 py-3">Plan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse ($sites as $site)
                    <tr data-href="{{ route('admin.sites.show', $site->slug) }}" class="cursor-pointer hover:bg-muted">
                        <td class="px-4 py-3 font-medium">
                            <a href="{{ route('admin.sites.show', $site->slug) }}" class="hover:underline">{{ $site->name }}</a>
                        </td>
                        <td class="px-4 py-3 text-secondary-foreground">
                            {{ $site->owners->pluck('name')->join(', ') ?: '—' }}
                        </td>
                        <td class="px-4 py-3">{!! \App\Models\SitePlan::badge($site->plan) !!}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-8 text-center text-secondary-foreground">No sites yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/admin.sites.index.js') }}"></script>
@endpush
