@extends('layouts.admin', [
    'title' => 'Pages',
    'pageTitle' => 'Pages',
    'pageDescription' => 'Pages synced from your frontend manifest.',
    'breadcrumbs' => [
        ['label' => 'Pages'],
    ],
])

@section('content')
    <div class="overflow-hidden rounded-lg border">
        <table class="min-w-full divide-y divide-border text-sm">
            <thead class="bg-muted text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                <tr>
                    <th class="px-4 py-3">Title</th>
                    <th class="px-4 py-3">Slug</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Blocks</th>
                    <th class="px-4 py-3">Updated</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse ($pages as $page)
                    <tr data-href="{{ route('pages.edit', $page) }}" class="cursor-pointer hover:bg-muted">
                        <td class="px-4 py-3 font-medium">
                            <a href="{{ route('pages.edit', $page) }}">{{ $page->title }}</a>
                        </td>
                        <td class="px-4 py-3 text-secondary-foreground">/{{ $page->slug }}</td>
                        <td class="px-4 py-3">
                            {!! \App\Models\PageStatus::badge($page->status) !!}
                        </td>
                        <td class="px-4 py-3 text-secondary-foreground">{{ $page->blocks_count }}</td>
                        <td class="px-4 py-3 text-secondary-foreground">{{ $page->updated_at->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-secondary-foreground">
                            No pages yet. Sync your frontend manifest to create them.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/pages.index.js') }}"></script>
@endpush
