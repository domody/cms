@props(['folder' => null, 'paths'])

@php
    // A parent already at MAX_DEPTH cannot take a child.
    $parents = $paths->reject(fn ($path) => substr_count($path, ' / ') + 1 >= \App\Models\MediaFolder::MAX_DEPTH);
@endphp

<x-modal title="New folder" :open="$errors->has('name') && ! old('_rename')">
    <x-slot:trigger>
        <button type="button" class="nav-link gap-1.5 font-medium">
            <x-hugeicons-folder-add class="size-4 shrink-0" aria-hidden="true" />
            New folder
        </button>
    </x-slot:trigger>
    <form method="POST" action="{{ route('media-folders.store') }}" class="space-y-3">
        @csrf
        <input type="text" name="name" value="{{ old('name') }}" required maxlength="100" placeholder="Folder name" aria-label="Folder name" class="input">
        @error('name') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        <label class="block space-y-1 text-sm font-medium">
            Create in
            <select name="parent_id" class="input font-normal">
                <option value="">Library (top level)</option>
                @foreach ($parents as $id => $path)
                    <option value="{{ $id }}" @selected((string) old('parent_id', $folder?->id) === (string) $id)>{{ $path }}</option>
                @endforeach
            </select>
        </label>
        @error('parent_id') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        <div class="text-right"><button type="submit" class="btn">Create</button></div>
    </form>
</x-modal>
