@extends('layouts.admin', [
    'title' => 'Media',
    'breadcrumbs' => [
        ['label' => 'Media'],
    ],
])

@php
    $percent = $storageLimit ? min(100, round($storageUsed / $storageLimit * 100)) : null;
@endphp

@section('headerActions')
    <div class="w-56 text-xs text-secondary-foreground">
        <div class="flex justify-between gap-2">
            @if ($storageLimit)
                <span>{{ \App\Models\Media::formatBytes($storageUsed) }} used of {{ \App\Models\Media::formatBytes($storageLimit) }}</span>
            @else
                <span>{{ \App\Models\Media::formatBytes($storageUsed) }} used</span>
                <span>No limit</span>
            @endif
        </div>
        @if ($percent !== null)
            <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-muted">
                <div class="h-full {{ $percent >= 90 ? 'bg-red-500' : 'bg-gray-900' }}" style="width: {{ $percent }}%"></div>
            </div>
        @endif
    </div>
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
                        <div class="absolute left-0 z-10 mt-1 w-40 rounded-lg border bg-white py-1 text-sm font-normal shadow-lg">
                            <x-media-folder-rename-modal :folder="$folder" />
                            <x-media-folder-delete-modal :folder="$folder" />
                        </div>
                    </details>
                @endif
            @endif

            <form method="GET" action="{{ route('media.index') }}" class="flex items-center gap-3 ms-auto" data-live-search-form>
                @if ($folder) <input type="hidden" name="folder" value="{{ $folder->id }}"> @endif
                <label class="flex items-center gap-1.5 whitespace-nowrap text-sm">
                    <input type="checkbox" name="subfolders" value="1" @checked($subfolders)>
                    Include subfolders
                </label>
                <input type="search" name="q" value="{{ $search }}" maxlength="100" placeholder="Search name or alt text" class="input w-64">
            </form>
        </div>

        @if ($search === '')
            @if ($folders->isNotEmpty())
                <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($folders as $child)
                        <a href="{{ route('media.index', ['folder' => $child->id]) }}" class="flex items-center gap-2 rounded-lg border px-3 py-2 text-sm hover:bg-muted">
                            <x-hugeicons-folder-01 class="size-4 shrink-0" aria-hidden="true" />
                            <span class="truncate">{{ $child->name }}</span>
                            <span class="ml-auto text-xs text-secondary-foreground">{{ $child->media_count }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        @endif

        {{-- Bulk actions (the checkboxes below belong to this form) --}}
        <form id="bulk-form" method="POST" class="hidden flex-wrap items-center gap-2 text-sm">
            @csrf
            <span class="text-muted-foreground"><span data-selected-count>0</span> selected</span>
            <select name="folder_id" class="input w-auto py-1">
                <option value="">Library root</option>
                @foreach ($folderPaths as $id => $path)
                    <option value="{{ $id }}">{{ $path }}</option>
                @endforeach
            </select>
            <button type="submit" formaction="{{ route('media.bulk-move') }}" class="btn-outline">Move selected</button>
            @if ($canDelete)
                <button type="submit" formaction="{{ route('media.bulk-delete') }}" data-confirm="Delete the selected images? Images that are in use are kept." class="btn-danger-outline">Delete selected</button>
            @endif
            @error('ids') <span class="text-xs text-red-600">Select at least one image.</span> @enderror
            <button type="button" data-clear-selection class="ml-auto text-muted-foreground hover:underline">Clear</button>
        </form>

        {{-- Media items --}}
        @if ($media->isEmpty())
            <div class="rounded-lg border px-4 py-8 text-center text-sm text-secondary-foreground">
                @if ($search !== '')
                    No images match your search.
                @elseif ($folders->isNotEmpty())
                    No images directly in {{ $folder?->name ?? 'the library' }}. Open a folder above.
                @else
                    No images here yet. Upload one above.
                @endif
            </div>
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
