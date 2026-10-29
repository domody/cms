@props(['folder'])

<x-modal title="Delete folder">
    <x-slot:trigger>
        <button type="button" class="block w-full px-3 py-1.5 text-left text-red-600 hover:bg-muted">Delete folder</button>
    </x-slot:trigger>
    <form method="POST" action="{{ route('media-folders.destroy', $folder) }}" class="space-y-3">
        @csrf
        @method('DELETE')
        <p class="text-sm">Delete the folder &ldquo;{{ $folder->name }}&rdquo;? Its images and subfolders move up one level.</p>
        <div class="text-right"><button type="submit" class="btn-danger">Delete</button></div>
    </form>
</x-modal>
