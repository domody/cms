@extends('layouts.admin', [
    'title' => 'Edit '.$page->title,
    'pageTitle' => $page->title,
    'pageDescription' => '/'.$page->slug,
    'breadcrumbs' => [
        ['label' => 'Pages', 'route' => route('pages.index')],
        ['label' => $page->title, 'route' => route('pages.show', $page)],
        ['label' => 'Edit'],
    ],
])

@section('pageActions')
    <x-button variant="subtle" icon="clock-01" :href="route('history.index', ['type' => 'page', 'subject' => $page->id])">History</x-button>
    <x-button variant="subtle" icon="view" :href="route('pages.show', $page)">Preview</x-button>
    <x-button type="submit" form="page-editor-form">Save changes</x-button>
@endsection

@section('content')
    <livewire:page-editor :page="$page" />
@endsection

@push('scripts')
    <script src="{{ asset('js/livewire.unsaved-guard.js') }}"></script>
@endpush
