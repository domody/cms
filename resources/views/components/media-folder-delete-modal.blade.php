@props(['folder'])

<x-modal title="Delete folder">
    <x-slot:trigger>
        <button type="button" class="block w-full rounded-md px-2.5 py-1.5 text-left text-danger hover:bg-danger/10">Delete folder</button>
    </x-slot:trigger>
    <form method="POST" action="{{ route('media-folders.destroy', $folder) }}" class="space-y-4">
        @csrf
        @method('DELETE')
        <p class="text-sm">Delete the folder &ldquo;{{ $folder->name }}&rdquo;? Its images and subfolders move up one level.</p>
        <div class="flex justify-end"><x-button type="submit" variant="danger">Delete</x-button></div>
    </form>
</x-modal>
