@props(['folder'])

<x-modal title="Rename folder" :open="old('_rename') && $errors->has('name')">
    <x-slot:trigger>
        <button type="button" class="block w-full rounded-md px-2.5 py-1.5 text-left hover:bg-muted">Rename folder</button>
    </x-slot:trigger>
    <form method="POST" action="{{ route('media-folders.update', $folder) }}" class="space-y-4">
        @csrf
        <input type="hidden" name="_rename" value="1">
        <x-field label="Name" :error-keys="old('_rename') ? ['name'] : []">
            <input type="text" name="name" value="{{ old('_rename') ? old('name') : $folder->name }}" required maxlength="100" class="input">
        </x-field>
        <div class="flex justify-end"><x-button type="submit">Rename</x-button></div>
    </form>
</x-modal>
