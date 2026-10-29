@props(['folder' => null, 'extensions' => [], 'maxMegabytes' => null])

<x-modal title="Upload image" :open="$errors->hasAny(['file', 'alt'])">
    <x-slot:trigger>
        <button type="button" class="btn">
            <x-hugeicons-upload-04 aria-hidden="true" />
            Upload
        </button>
    </x-slot:trigger>
    <form method="POST" action="{{ route('media.store') }}" enctype="multipart/form-data" class="space-y-3">
        @csrf
        <input type="hidden" name="folder_id" value="{{ $folder?->id }}">
        <div class="text-sm font-medium">
            <div class="relative mt-1 flex h-24 flex-col items-center justify-center gap-1 rounded-lg border border-dashed text-secondary-foreground hover:bg-muted">
                <x-hugeicons-upload-04 aria-hidden="true" class="size-6" />
                <span data-file-label>Drag &amp; Drop or <span class="underline">Browse</span></span>
                <input type="file" name="file" required accept="{{ collect($extensions)->map(fn ($ext) => ".$ext")->join(',') }}" aria-label="Image file" onchange="this.parentElement.querySelector('[data-file-label]').textContent = this.files[0]?.name ?? 'Drag & Drop or Browse'" class="absolute inset-0 size-full cursor-pointer opacity-0">
            </div>
            @error('file') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <label class="block text-sm font-medium">
            Alt text <span class="text-red-600">*</span>
            <input type="text" name="alt" value="{{ old('alt') }}" required maxlength="255" placeholder="Describe the image" class="input mt-1">
            @error('alt') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </label>
        <p class="text-xs text-secondary-foreground">
            {{ strtoupper(collect($extensions)->join(', ')) }}, up to {{ $maxMegabytes }} MB and {{ \App\Models\Media::MAX_DIMENSION }} px on a side.
        </p>
        <div class="text-right"><button type="submit" class="btn">Upload</button></div>
    </form>
</x-modal>
