@props(['folder'])

<x-modal title="Rename folder" :open="old('_rename') && $errors->has('name')">
    <x-slot:trigger>
        <button type="button" class="block w-full px-3 py-1.5 text-left hover:bg-muted">Rename folder</button>
    </x-slot:trigger>
    <form method="POST" action="{{ route('media-folders.update', $folder) }}" class="space-y-3">
        @csrf
        <input type="hidden" name="_rename" value="1">
        <input type="text" name="name" value="{{ old('_rename') ? old('name') : $folder->name }}" required maxlength="100" aria-label="Folder name" class="input">
        @if (old('_rename')) @error('name') <p class="text-xs text-red-600">{{ $message }}</p> @enderror @endif
        <div class="text-right"><button type="submit" class="btn">Rename</button></div>
    </form>
</x-modal>
