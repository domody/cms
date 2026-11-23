@extends('layouts.admin', [
    'title' => $collection->label,
    'pageTitle' => $collection->label,
    'pageDescription' => 'Drag entries to change the order they appear in.',
    'breadcrumbs' => [
        ['label' => 'Collections', 'route' => route('collections.index')],
        ['label' => $collection->label],
    ],
])

@section('pageActions')
    <x-button icon="add-01" :href="route('collections.create', ['collection' => $collection->key])">Add entry</x-button>
@endsection

@section('content')
    <livewire:collection-entry-list :definition="$collection" />
@endsection
