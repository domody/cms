@extends('layouts.admin', [
    'title' => $entry ? 'Edit '.$entry->title : 'New '.$collection->label.' entry',
    'pageTitle' => $entry ? $entry->title : 'New entry',
    'pageDescription' => $collection->label,
    'breadcrumbs' => [
        ['label' => 'Collections', 'route' => route('collections.index')],
        ['label' => $collection->label, 'route' => route('collections.show', ['collection' => $collection->key])],
        ['label' => $entry ? 'Edit' : 'New'],
    ],
])

@section('pageActions')
    <div class="flex items-center gap-3">
        @if ($entry)
            <a href="{{ route('history.index', ['type' => 'collection_entry', 'subject' => $entry->id]) }}" class="text-sm text-gray-600 hover:text-gray-900">History</a>
        @endif
        <a href="{{ route('collections.show', ['collection' => $collection->key]) }}" class="text-sm text-gray-600 hover:text-gray-900">Back to list</a>
        <button type="submit" form="entry-editor-form" class="btn">
            Save entry
        </button>
    </div>
@endsection

@section('content')
    <livewire:collection-entry-editor :definition="$collection" :entry="$entry" />
@endsection

@push('scripts')
    <script src="{{ asset('js/livewire.unsaved-guard.js') }}"></script>
@endpush
