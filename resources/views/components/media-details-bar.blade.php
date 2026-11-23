@props(['item' => null, 'usages' => collect(), 'folderPaths', 'canDelete' => false])

@if ($item === null)
    <x-empty-state icon="image-01" title="No image selected" aria-label="Image details" class="w-full shrink-0 bg-muted lg:w-80">
        Pick an image to edit its alt text, move it, see where it's used or replace the file.
    </x-empty-state>
@else
    <aside aria-label="Image details" class="w-full shrink-0 overflow-hidden rounded-xl bg-background ring-1 ring-border lg:sticky lg:top-20 lg:w-80">
        <div class="flex items-center justify-between border-b py-1.5 pe-1.5 ps-4">
            <h2 class="text-sm font-semibold">Details</h2>
            <x-icon-button icon="cancel-01" label="Close details" :href="request()->fullUrlWithQuery(['media' => null])" />
        </div>
        <img src="{{ $item->variantUrl('medium') }}" alt="{{ $item->alt }}" class="aspect-video w-full bg-muted object-cover">

        <div class="space-y-5 p-4">
            <div>
                <p class="break-all text-sm font-semibold">{{ $item->original_name }}</p>
                <p class="mt-0.5 text-xs text-secondary-foreground">
                    @if ($item->width) {{ $item->width }}&times;{{ $item->height }} &middot; @endif
                    {{ \App\Models\Media::formatBytes($item->size) }}
                </p>
            </div>

            <form method="POST" action="{{ route('media.update', $item) }}" class="space-y-4">
                @csrf
                <x-field label="Alt text" name="alt" required>
                    <input type="text" name="alt" value="{{ old('alt', $item->alt) }}" maxlength="255" required class="input">
                </x-field>
                <x-field label="Folder" name="folder_id">
                    <select name="folder_id" class="input">
                        <option value="">Library root</option>
                        @foreach ($folderPaths as $id => $path)
                            <option value="{{ $id }}" @selected($item->folder_id === $id)>{{ $path }}</option>
                        @endforeach
                    </select>
                </x-field>
                <x-button type="submit" class="w-full">Save changes</x-button>
            </form>

            <div class="border-t pt-4">
                <p class="text-sm font-medium">
                    {{ $usages->isEmpty() ? 'Not used anywhere yet' : 'Used in '.$usages->count().($usages->count() === 1 ? ' place' : ' places') }}
                </p>
                @if ($usages->isNotEmpty())
                    <ul class="mt-2 space-y-1">
                        @foreach ($usages as $usage)
                            <li><a href="{{ $usage['url'] }}" class="text-sm text-secondary-foreground underline underline-offset-2 hover:text-foreground">{{ $usage['label'] }}</a></li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="space-y-2 border-t pt-4">
                <form method="POST" action="{{ route('media.replace', $item) }}" enctype="multipart/form-data">
                    @csrf
                    <label class="btn-outline w-full cursor-pointer">
                        <x-hugeicons-arrow-reload-horizontal aria-hidden="true" />
                        Replace file
                        <input type="file" name="file" accept="{{ collect(\App\Models\Media::EXTENSIONS)->map(fn ($e) => ".{$e}")->join(',') }}" class="sr-only" data-auto-submit>
                    </label>
                    @error('file') <span class="mt-1 block text-xs font-medium text-danger">{{ $message }}</span> @enderror
                </form>
                <p class="text-xs text-secondary-foreground">Every page using this image will show the new file.</p>

                @if ($canDelete)
                    <form method="POST" action="{{ route('media.destroy', $item) }}" data-confirm="{{ $usages->isEmpty() ? 'Delete this image?' : 'This image is used in '.$usages->count().' place(s); those fields will show no image. Delete it anyway?' }}">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="force" value="1">
                        <x-button type="submit" variant="danger-ghost" icon="delete-02" class="-ms-2">Delete image</x-button>
                    </form>
                @endif
            </div>
        </div>
    </aside>
@endif
