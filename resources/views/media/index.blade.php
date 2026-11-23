@extends('layouts.admin', [
    'title' => 'Media',
    'breadcrumbs' => [
        ['label' => 'Media'],
    ],
])

@section('headerActions')
    <x-usage-meter :used="$storageUsed" :limit="$storageLimit" class="w-56" />
    <x-media-upload-modal :folder="$folder" :extensions="$extensions" :max-megabytes="$maxMegabytes" />
@endsection

@section('content')
    <div class="flex flex-wrap items-start gap-6 pb-8" id="media-content">
        {{-- Folder tree --}}
        <nav aria-label="Folders" class="w-full shrink-0 space-y-1 md:w-60">
            <div class="flex items-center justify-between pl-2">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-secondary-foreground">Folders</h2>
                <x-media-folder-create-modal :folder="$folder" :paths="$folderPaths" />
            </div>
            <a href="{{ route('media.index') }}" class="nav-link" @if ($folder === null && $search === '') aria-current="page" @endif>
                <x-hugeicons-folder-01 class="size-4 shrink-0" aria-hidden="true" />
                Library
                <span class="ml-auto text-xs text-secondary-foreground">{{ $libraryCount }}</span>
            </a>
            <div class="ml-3 border-l pl-1">
                <x-media-folder-tree :nodes="$folderTree" :current="$folder" :open-ids="$trail->pluck('id')" />
            </div>
        </nav>

        <div class="min-w-0 flex-1 space-y-4">
        {{-- Where you are, and what you can do to this folder --}}
        <div class="flex flex-wrap items-center gap-1 text-sm">
            @if ($search === '')
                <nav aria-label="Folder path" class="flex flex-wrap items-center gap-1">
                    @if ($folder)
                        <a href="{{ route('media.index') }}" class="rounded px-1 text-secondary-foreground underline-offset-2 hover:underline">Library</a>
                        @foreach ($trail as $crumb)
                            <x-hugeicons-arrow-right-01 class="size-3.5 text-secondary-foreground" aria-hidden="true" />
                            @if ($loop->last)
                                <span class="px-1 font-semibold" aria-current="page">{{ $crumb->name }} <span class="ml-1 text-xs font-normal text-secondary-foreground">{{ $folder->media_count }}</span></span>
                            @else
                                <a href="{{ route('media.index', ['folder' => $crumb->id]) }}" class="rounded px-1 text-secondary-foreground underline-offset-2 hover:underline">{{ $crumb->name }}</a>
                            @endif
                        @endforeach
                    @else
                        <span class="px-1 font-semibold" aria-current="page">Library</span>
                    @endif
                </nav>

                @if ($folder)
                    <details class="relative">
                        <summary class="btn-icon-ghost list-none" aria-label="Folder actions">
                            <x-hugeicons-more-vertical aria-hidden="true" />
                        </summary>
                        <div class="absolute left-0 z-10 mt-1 w-40 rounded-lg bg-background p-1 text-sm font-normal shadow-lg ring-1 ring-border">
                            <x-media-folder-rename-modal :folder="$folder" />
                            <x-media-folder-delete-modal :folder="$folder" />
                        </div>
                    </details>
                @endif
            @endif

            <form method="GET" action="{{ route('media.index') }}" class="flex items-center gap-3 ms-auto" data-live-search-form>
                @if ($folder) <input type="hidden" name="folder" value="{{ $folder->id }}"> @endif
                <label class="flex items-center gap-1.5 whitespace-nowrap text-sm">
                    <input type="checkbox" name="subfolders" value="1" @checked($subfolders) class="checkbox">
                    Include subfolders
                </label>
                <input type="search" name="q" value="{{ $search }}" maxlength="100" placeholder="Search name or alt text" class="input h-8 w-64 py-1">
            </form>
        </div>

        @if ($search === '')
            @if ($folders->isNotEmpty())
                <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($folders as $child)
                        <a href="{{ route('media.index', ['folder' => $child->id]) }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm ring-1 ring-border hover:bg-muted">
                            <x-hugeicons-folder-01 class="size-4 shrink-0" aria-hidden="true" />
                            <span class="truncate">{{ $child->name }}</span>
                            <span class="ml-auto text-xs text-secondary-foreground">{{ $child->media_count }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        @endif

        {{-- Bulk actions (the checkboxes below belong to this form) --}}
        <form id="bulk-form" method="POST" class="hidden flex-wrap items-center gap-2 rounded-xl bg-muted px-3 py-2 text-sm">
            @csrf
            <span class="text-muted-foreground"><span data-selected-count>0</span> selected</span>
            <select name="folder_id" aria-label="Move to folder" class="input h-8 w-auto bg-background py-1">
                <option value="">Library root</option>
                @foreach ($folderPaths as $id => $path)
                    <option value="{{ $id }}">{{ $path }}</option>
                @endforeach
            </select>
            <x-button type="submit" variant="outline" :formaction="route('media.bulk-move')">Move selected</x-button>
            @if ($canDelete)
                <x-button type="submit" variant="danger-outline" :formaction="route('media.bulk-delete')" data-confirm="Delete the selected images? Images that are in use are kept.">Delete selected</x-button>
            @endif
            @error('ids') <span class="text-xs font-medium text-danger">Select at least one image.</span> @enderror
            <x-button variant="subtle" data-clear-selection class="ml-auto">Clear</x-button>
        </form>

        {{-- Media items --}}
        @if ($media->isEmpty())
            @if ($search !== '')
                <x-empty-state figure="loupe" title="No images match">Try a different search.</x-empty-state>
            @elseif ($folders->isNotEmpty())
                <x-empty-state figure="drawer" title="No images here yet">Open a folder or upload one.</x-empty-state>
            @else
                <x-empty-state figure="drawer" title="No images yet">Upload your first image to start your library.</x-empty-state>
            @endif
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($media as $item)
                    <x-media-item :item="$item" :selected="$selected?->id === $item->id" :usage-count="$item->usages->count()" />
                @endforeach
            </div>

            <div class="py-2">{{ $media->links() }}</div>
        @endif
        </div>

        <x-media-details-bar :item="$selected" :usages="$selectedUsages" :folder-paths="$folderPaths" :can-delete="$canDelete" />
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/media.index.js') }}"></script>
@endpush
