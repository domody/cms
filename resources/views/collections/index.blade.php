@extends('layouts.admin', [
    'title' => 'Collections',
    'pageTitle' => 'Collections',
    'pageDescription' => 'Repeatable content, like team members. Open one to manage its entries.',
    'breadcrumbs' => [
        ['label' => 'Collections'],
    ],
])

@section('content')
    @if ($collections->isEmpty())
        <x-empty-state figure="cabinet" title="No collections yet">
            Collections appear once your frontend declares them.
        </x-empty-state>
    @else
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($collections as $collection)
                <x-link-tile :href="route('collections.show', ['collection' => $collection->key])" icon="folder-01" :title="$collection->label">
                    {{ trans_choice('{0} No entries|{1} 1 entry|[2,*] :count entries', $entryCounts[$collection->key] ?? 0) }}
                </x-link-tile>
            @endforeach
        </div>
    @endif
@endsection
