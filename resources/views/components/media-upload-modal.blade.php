@props(['folder' => null, 'extensions' => [], 'maxMegabytes' => null])

<x-modal title="Upload image" :open="$errors->hasAny(['file', 'alt'])">
    <x-slot:trigger>
        <x-button icon="upload-04">Upload</x-button>
    </x-slot:trigger>
    <form method="POST" action="{{ route('media.store') }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        <input type="hidden" name="folder_id" value="{{ $folder?->id }}">
        <x-field as="div" name="file" :hint="strtoupper(collect($extensions)->join(', ')).', up to '.$maxMegabytes.' MB and '.\App\Models\Media::MAX_DIMENSION.' px on a side.'">
            <div class="relative flex h-28 flex-col items-center justify-center gap-1.5 rounded-lg border border-dashed text-sm text-secondary-foreground transition-colors hover:border-foreground/30 hover:bg-muted">
                <x-hugeicons-upload-04 aria-hidden="true" class="size-6" />
                <span data-file-label>Drag &amp; Drop or <span class="underline">Browse</span></span>
                <input type="file" name="file" required accept="{{ collect($extensions)->map(fn ($ext) => ".$ext")->join(',') }}" aria-label="Image file" onchange="this.parentElement.querySelector('[data-file-label]').textContent = this.files[0]?.name ?? 'Drag & Drop or Browse'" class="absolute inset-0 size-full cursor-pointer opacity-0">
            </div>
        </x-field>
        <x-field label="Alt text" name="alt" required>
            <input type="text" name="alt" value="{{ old('alt') }}" required maxlength="255" placeholder="Describe the image" class="input">
        </x-field>
        <div class="flex justify-end"><x-button type="submit">Upload</x-button></div>
    </form>
</x-modal>
