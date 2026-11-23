<div>
    @php($selected = $this->selected)

    @if ($tile && $selected)
        <div class="overflow-hidden rounded-lg bg-input">
            <button type="button" wire:click="openLibrary" class="group/image relative block w-full" aria-label="Change image {{ $selected->original_name }}">
                <img src="{{ $selected->variantUrl('thumb') }}" alt="{{ $selected->alt }}" class="aspect-[4/3] w-full object-cover">
                <span class="absolute inset-0 grid place-items-center bg-black/40 text-sm font-medium text-white opacity-0 transition-opacity group-hover/image:opacity-100 group-focus-visible/image:opacity-100">Change</span>
            </button>
            <div class="px-2.5 py-2">
                <p class="truncate text-xs font-medium">{{ $selected->original_name }}</p>
                <p class="truncate text-xs text-secondary-foreground">{{ $selected->alt ?: 'No alt text' }}</p>
            </div>
        </div>
    @elseif ($tile)
        <button type="button" wire:click="openLibrary" class="flex aspect-[4/3] w-full flex-col items-center justify-center gap-2 rounded-lg border border-dashed text-sm text-secondary-foreground transition-colors hover:border-foreground/30 hover:bg-muted hover:text-foreground">
            <x-hugeicons-image-add-01 class="size-6" aria-hidden="true" />
            Choose image
        </button>
    @elseif ($selected)
        <div class="flex items-center gap-3 rounded-lg bg-input p-1.5 pe-2">
            <img src="{{ $selected->variantUrl('thumb') }}" alt="{{ $selected->alt }}" class="size-11 shrink-0 rounded-md object-cover">
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium">{{ $selected->original_name }}</p>
                <p class="truncate text-xs text-secondary-foreground">{{ $selected->alt ?: 'No alt text' }}</p>
            </div>
            <x-button variant="ghost" icon="exchange-01" wire:click="openLibrary">Change</x-button>
            <x-icon-button icon="delete-02" label="Remove image" tone="danger" wire:click="clear" />
        </div>
    @else
        <button type="button" wire:click="openLibrary" class="flex w-full items-center gap-3 rounded-lg border border-dashed p-1.5 text-start text-sm text-secondary-foreground transition-colors hover:border-foreground/30 hover:bg-muted hover:text-foreground">
            <span class="grid size-11 shrink-0 place-items-center rounded-md bg-muted">
                <x-hugeicons-image-add-01 class="size-5" aria-hidden="true" />
            </span>
            Choose or upload an image
        </button>
    @endif

    @if ($open)
        <x-dialog title="Choose an image" close="$wire.closeLibrary()" size="lg">
            <div class="grid gap-3 rounded-lg bg-muted p-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
                <x-field as="div" label="Upload new" name="file">
                    <input type="file" wire:model="file" accept="{{ collect(\App\Models\Media::EXTENSIONS)->map(fn ($ext) => ".$ext")->join(',') }}" aria-label="Image file" class="block w-full text-sm file:me-3 file:rounded-md file:border-0 file:bg-background file:px-3 file:py-1.5 file:text-sm file:font-medium file:ring-1 file:ring-border">
                </x-field>
                <x-field label="Alt text" name="alt" required>
                    <input type="text" wire:model="alt" wire:keydown.enter.prevent="saveUpload" placeholder="Describe the image" class="input bg-background">
                </x-field>
                <x-button icon="upload-04" wire:click="saveUpload" wire:loading.attr="disabled">Upload</x-button>
            </div>

            <div class="mb-3 mt-6 flex flex-wrap items-center gap-2">
                <h3 class="me-auto text-sm font-semibold">Library</h3>
                <select wire:model.live="folder" aria-label="Folder" class="input h-8 w-auto py-1">
                    <option value="">All folders</option>
                    <option value="root">Library root</option>
                    @foreach (\App\Models\MediaFolder::paths() as $id => $path)
                        <option value="{{ $id }}">{{ $path }}</option>
                    @endforeach
                </select>
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search" aria-label="Search images" class="input h-8 w-48 py-1">
            </div>

            @php($library = $this->library)
            @if ($library->isEmpty())
                @if ($search !== '' || $folder !== '')
                    <x-empty-state figure="loupe" title="No images match">Try a different search or folder.</x-empty-state>
                @else
                    <x-empty-state figure="drawer" title="No images yet">Upload your first image to start your library.</x-empty-state>
                @endif
            @else
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                    @foreach ($library->take($limit) as $item)
                        <button type="button" wire:key="media-{{ $item->id }}" wire:click="choose({{ $item->id }})" @class([
                            'overflow-hidden rounded-lg text-start ring-1 transition hover:ring-foreground/40',
                            'ring-2 ring-foreground' => $item->id === $mediaId,
                            'ring-border' => $item->id !== $mediaId,
                        ])>
                            <img src="{{ $item->variantUrl('thumb') }}" alt="{{ $item->alt }}" loading="lazy" class="aspect-video w-full bg-muted object-cover">
                            <span class="block truncate px-2 py-1.5 text-xs text-secondary-foreground">{{ $item->original_name }}</span>
                        </button>
                    @endforeach
                </div>
                @if ($library->count() > $limit)
                    <div class="mt-4 text-center">
                        <x-button variant="outline" wire:click="loadMore">Load more</x-button>
                    </div>
                @endif
            @endif
        </x-dialog>
    @endif
</div>
