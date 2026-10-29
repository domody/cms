@props(['item' => null, 'usages' => collect(), 'folderPaths', 'canDelete' => false])

@if ($item === null)
    <aside aria-label="Image details" class="flex w-full shrink-0 flex-col items-center gap-3 rounded-lg border border-dashed bg-muted px-6 py-12 text-center lg:w-80">
        <x-hugeicons-image-01 class="size-10 text-secondary-foreground" aria-hidden="true" />
        <h2 class="text-sm font-semibold">No image selected</h2>
        <p class="max-w-56 text-sm text-secondary-foreground">Pick an image to edit its alt text, move it, see where it's used or replace the file.</p>
    </aside>
@else
    <aside aria-label="Image details" class="w-full shrink-0 overflow-hidden rounded-lg border bg-white lg:w-80">
        <div class="flex items-center justify-between border-b px-4 py-2">
            <h2 class="text-sm font-semibold">Details</h2>
            <a href="{{ request()->fullUrlWithQuery(['media' => null]) }}" class="btn-icon" aria-label="Close details">
                <x-hugeicons-cancel-01 aria-hidden="true" />
            </a>
        </div>
        <img src="{{ $item->variantUrl('medium') }}" alt="{{ $item->alt }}" class="aspect-video w-full bg-muted object-cover">

        <div class="space-y-4 p-4">
            <div>
                <p class="break-all font-semibold">{{ $item->original_name }}</p>
                <p class="mt-0.5 text-xs text-secondary-foreground">
                    @if ($item->width) {{ $item->width }}&times;{{ $item->height }} &middot; @endif
                    {{ \App\Models\Media::formatBytes($item->size) }}
                </p>
            </div>

            <form method="POST" action="{{ route('media.update', $item) }}" class="space-y-4">
                @csrf
                <label class="block space-y-1.5 font-medium">
                    Alt text
                    <input type="text" name="alt" value="{{ old('alt', $item->alt) }}" maxlength="255" required class="input font-normal">
                    @error('alt') <span class="block text-xs font-normal text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="block space-y-1.5 font-medium">
                    Folder
                    <select name="folder_id" class="input font-normal">
                        <option value="">Library root</option>
                        @foreach ($folderPaths as $id => $path)
                            <option value="{{ $id }}" @selected($item->folder_id === $id)>{{ $path }}</option>
                        @endforeach
                    </select>
                </label>
                <button type="submit" class="btn w-full">Save changes</button>
            </form>

            <div class="border-t pt-4">
                <p class="font-medium">
                    {{ $usages->isEmpty() ? 'Not used anywhere yet' : 'Used in '.$usages->count().($usages->count() === 1 ? ' place' : ' places') }}
                </p>
                <ul class="mt-2 space-y-1">
                    @foreach ($usages as $usage)
                        <li><a href="{{ $usage['url'] }}" class="text-sm text-blue-700 underline">{{ $usage['label'] }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div class="space-y-2 border-t pt-4">
                <form method="POST" action="{{ route('media.replace', $item) }}" enctype="multipart/form-data">
                    @csrf
                    <label class="btn-outline w-full cursor-pointer">
                        Replace file
                        <input type="file" name="file" accept="{{ collect(\App\Models\Media::EXTENSIONS)->map(fn ($e) => ".{$e}")->join(',') }}" class="sr-only" data-auto-submit>
                    </label>
                    @error('file') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </form>
                <p class="text-xs text-secondary-foreground">Every page using this image will show the new file.</p>

                @if ($canDelete)
                    <form method="POST" action="{{ route('media.destroy', $item) }}" data-confirm="{{ $usages->isEmpty() ? 'Delete this image?' : 'This image is used in '.$usages->count().' place(s); those fields will show no image. Delete it anyway?' }}">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="force" value="1">
                        <button type="submit" class="text-sm font-medium text-red-600 hover:underline">Delete image</button>
                    </form>
                @endif
            </div>
        </div>
    </aside>
@endif
