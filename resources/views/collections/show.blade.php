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
    <a href="{{ route('collections.create', ['collection' => $collection->key]) }}" class="btn">
        <x-hugeicons-add-01 aria-hidden="true" />
        Add entry
    </a>
@endsection

@section('content')
    <livewire:collection-entry-list :definition="$collection" />
@endsection
