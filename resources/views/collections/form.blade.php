@extends('layouts.admin', [
    'title' => $entry ? 'Edit '.$entry->title : 'New '.$collection->label.' entry',
    'pageTitle' => $entry ? $entry->title : 'New entry',
    'pageDescription' => $collection->label,
    'breadcrumbs' => [
        ['label' => 'Collections', 'route' => route('collections.index')],
        ['label' => $collection->label, 'route' => route('collections.show', ['collection' => $collection->key])],
        ['label' => $entry ? $entry->title : 'New entry'],
    ],
])

@section('pageActions')
    @if ($entry)
        <x-button variant="subtle" icon="clock-01" :href="route('history.index', ['type' => 'collection_entry', 'subject' => $entry->id])">History</x-button>
    @endif
    <x-button type="submit" form="entry-editor-form">Save entry</x-button>
@endsection

@section('content')
    <livewire:collection-entry-editor :definition="$collection" :entry="$entry" />
@endsection

@push('scripts')
    <script src="{{ asset('js/livewire.unsaved-guard.js') }}"></script>
    <script src="{{ asset('js/collections.form.js') }}"></script>
@endpush
