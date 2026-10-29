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
        <div class="rounded-lg border px-4 py-8 text-center text-sm text-secondary-foreground">
            No collections yet. Sync your frontend manifest to create them.
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($collections as $collection)
                <a href="{{ route('collections.show', ['collection' => $collection->key]) }}" class="flex items-start gap-3 rounded-lg border bg-white p-4 hover:bg-muted">
                    <x-hugeicons-folder-01 class="mt-0.5 size-5 shrink-0 text-secondary-foreground" aria-hidden="true" />
                    <div class="min-w-0">
                        <div class="font-medium">{{ $collection->label }}</div>
                        <div class="mt-1 text-sm text-secondary-foreground">
                            {{ trans_choice('{0} No entries|{1} 1 entry|[2,*] :count entries', $entryCounts[$collection->key] ?? 0) }}
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
@endsection
