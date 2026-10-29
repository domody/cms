<div>
    @php($selected = $this->selected)

    <div class="mt-1 flex items-center gap-3">
        @if ($selected)
            <img src="{{ $selected->variantUrl('thumb') }}" alt="{{ $selected->alt }}" class="size-16 rounded border border-gray-200 object-cover">
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm text-gray-900">{{ $selected->original_name }}</p>
                <p class="truncate text-xs text-gray-500">{{ $selected->alt }}</p>
            </div>
            <button type="button" wire:click="openLibrary" class="btn-ghost">Change</button>
            <button type="button" wire:click="clear" class="btn-ghost-danger">Remove</button>
        @else
            <button type="button" wire:click="openLibrary" class="rounded-lg border border-dashed border-gray-300 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50">
                Choose or upload an image
            </button>
        @endif
    </div>

    @if ($open)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" wire:click.self="closeLibrary" wire:keydown.escape="closeLibrary">
            <div class="flex max-h-full w-full max-w-3xl flex-col overflow-hidden rounded-lg bg-white shadow-xl" role="dialog" aria-modal="true" aria-label="Choose an image">
                <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                    <h3 class="text-sm font-semibold text-gray-900">Choose an image</h3>
                    <button type="button" wire:click="closeLibrary" class="btn-ghost">Close</button>
                </div>

                <div class="overflow-y-auto p-4">
                    <div class="grid gap-3 rounded-lg border border-gray-200 p-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
                        <div class="text-sm font-medium text-gray-700">
                            Upload new
                            <input type="file" wire:model="file" accept="{{ collect(\App\Models\Media::EXTENSIONS)->map(fn ($ext) => ".$ext")->join(',') }}" class="mt-1 block w-full text-sm">
                            @error('file') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="text-sm font-medium text-gray-700">
                            Alt text <span class="text-red-600">*</span>
                            <input type="text" wire:model="alt" wire:keydown.enter.prevent="saveUpload" placeholder="Describe the image" class="input mt-1">
                            @error('alt') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <button type="button" wire:click="saveUpload" wire:loading.attr="disabled" class="btn">Upload</button>
                    </div>

                    <div class="mb-2 mt-5 flex flex-wrap items-center gap-2">
                        <h4 class="me-auto text-xs font-medium uppercase tracking-wide text-gray-500">Library</h4>
                        <select wire:model.live="folder" aria-label="Folder" class="input w-auto px-2 py-1 text-xs">
                            <option value="">All folders</option>
                            <option value="root">Library root</option>
                            @foreach (\App\Models\MediaFolder::paths() as $id => $path)
                                <option value="{{ $id }}">{{ $path }}</option>
                            @endforeach
                        </select>
                        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search" aria-label="Search images" class="input w-auto px-2 py-1 text-xs">
                    </div>
                    @php($library = $this->library)
                    @if ($library->isEmpty())
                        <p class="py-4 text-center text-sm text-gray-500">{{ $search !== '' || $folder !== '' ? 'No images match.' : 'No images yet. Upload one above.' }}</p>
                    @else
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                            @foreach ($library->take($limit) as $item)
                                <button type="button" wire:key="media-{{ $item->id }}" wire:click="choose({{ $item->id }})" class="overflow-hidden rounded-lg border text-start hover:border-gray-900 {{ $item->id === $mediaId ? 'border-gray-900 ring-2 ring-gray-900/20' : 'border-gray-200' }}">
                                    <img src="{{ $item->variantUrl('thumb') }}" alt="{{ $item->alt }}" loading="lazy" class="aspect-video w-full bg-gray-100 object-cover">
                                    <span class="block truncate px-2 py-1 text-xs text-gray-600">{{ $item->original_name }}</span>
                                </button>
                            @endforeach
                        </div>
                        @if ($library->count() > $limit)
                            <div class="mt-3 text-center">
                                <button type="button" wire:click="loadMore" class="btn-outline">Load more</button>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
