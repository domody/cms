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
        <x-empty-state icon="folder-01" title="No collections yet">
            Sync your frontend manifest to create them.
        </x-empty-state>
    @else
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($collections as $collection)
                <a href="{{ route('collections.show', ['collection' => $collection->key]) }}" class="group flex items-center gap-3 rounded-xl p-4 ring-1 ring-border transition hover:bg-muted hover:ring-foreground/20">
                    <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-muted text-secondary-foreground group-hover:bg-background">
                        <x-hugeicons-folder-01 class="size-5" aria-hidden="true" />
                    </span>
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-medium">{{ $collection->label }}</span>
                        <span class="block text-xs text-secondary-foreground">
                            {{ trans_choice('{0} No entries|{1} 1 entry|[2,*] :count entries', $entryCounts[$collection->key] ?? 0) }}
                        </span>
                    </span>
                    <x-hugeicons-arrow-right-01 class="ms-auto size-4 text-muted-foreground transition-transform group-hover:translate-x-0.5" aria-hidden="true" />
                </a>
            @endforeach
        </div>
    @endif
@endsection
