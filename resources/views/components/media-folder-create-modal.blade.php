@props(['folder' => null, 'paths'])

@php
    // A parent already at MAX_DEPTH cannot take a child.
    $parents = $paths->reject(fn ($path) => substr_count($path, ' / ') + 1 >= \App\Models\MediaFolder::MAX_DEPTH);
@endphp

<x-modal title="New folder" :open="$errors->has('name') && ! old('_rename')">
    <x-slot:trigger>
        <x-icon-button icon="folder-add" label="New folder" />
    </x-slot:trigger>
    <form method="POST" action="{{ route('media-folders.store') }}" class="space-y-4">
        @csrf
        <x-field label="Name" :error-keys="old('_rename') ? [] : ['name']">
            <input type="text" name="name" value="{{ old('name') }}" required maxlength="100" placeholder="Folder name" class="input">
        </x-field>
        <x-field label="Create in" name="parent_id">
            <select name="parent_id" class="input">
                <option value="">Library (top level)</option>
                @foreach ($parents as $id => $path)
                    <option value="{{ $id }}" @selected((string) old('parent_id', $folder?->id) === (string) $id)>{{ $path }}</option>
                @endforeach
            </select>
        </x-field>
        <div class="flex justify-end"><x-button type="submit">Create</x-button></div>
    </form>
</x-modal>
