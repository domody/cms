@extends('layouts.admin', [
    'title' => 'Pages',
    'pageTitle' => 'Pages',
    'pageDescription' => 'Pages synced from your frontend manifest.',
    'breadcrumbs' => [
        ['label' => 'Pages'],
    ],
])

@section('content')
    <x-table>
        <x-slot:head>
            <th>Title</th>
            <th>Status</th>
            <th>Blocks</th>
            <th>Updated</th>
        </x-slot:head>

        @forelse ($pages as $page)
            <tr data-href="{{ route('pages.edit', $page) }}" class="cursor-pointer hover:bg-muted">
                <td>
                    <a href="{{ route('pages.edit', $page) }}" class="font-medium">{{ $page->title }}</a>
                    <span class="ms-2 font-mono text-xs text-muted-foreground">/{{ $page->slug }}</span>
                </td>
                <td>{!! \App\Models\PageStatus::badge($page->status) !!}</td>
                <td class="tabular-nums text-secondary-foreground">{{ $page->blocks_count }}</td>
                <td class="text-secondary-foreground">{{ $page->updated_at->diffForHumans() }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="empty">No pages yet. Sync your frontend manifest to create them.</td>
            </tr>
        @endforelse
    </x-table>
@endsection

@push('scripts')
    <script src="{{ asset('js/pages.index.js') }}"></script>
@endpush
