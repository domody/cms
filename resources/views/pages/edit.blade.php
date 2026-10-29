@extends('layouts.admin', [
    'title' => 'Edit /'.$page->slug,
    'pageTitle' => 'Edit /'.$page->slug,
    'pageDescription' => $page->title,
    'breadcrumbs' => [
        ['label' => 'Pages', 'route' => route('pages.index')],
        ['label' => $page->title, 'route' => route('pages.show', $page)],
        ['label' => 'Edit'],
    ],
])

@section('pageActions')
    <div class="flex items-center gap-3">
        <a href="{{ route('history.index', ['type' => 'page', 'subject' => $page->id]) }}" class="text-sm text-gray-600 hover:text-gray-900">History</a>
        <a href="{{ route('pages.show', $page) }}" class="text-sm text-gray-600 hover:text-gray-900">View page</a>
        <button type="submit" form="page-editor-form" class="btn">
            Save changes
        </button>
    </div>
@endsection

@section('content')
    <livewire:page-editor :page="$page" />
@endsection

@push('scripts')
    <script src="{{ asset('js/livewire.unsaved-guard.js') }}"></script>
@endpush
