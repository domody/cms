<div class="rounded-lg border border-gray-200 bg-white">
    @if ($this->entries->isEmpty())
        <p class="px-4 py-8 text-center text-sm text-gray-500">
            No entries yet. Use "Add entry" to create the first one.
        </p>
    @else
        <div wire:sort="sortEntry">
            @foreach ($this->entries as $entry)
                <div
                    wire:key="entry-{{ $entry->id }}"
                    wire:sort:item="{{ $entry->id }}"
                    class="flex items-center gap-3 px-3 py-2 {{ $loop->last ? '' : 'border-b border-gray-100' }} hover:bg-gray-50"
                >
                    <button type="button" wire:sort:handle title="Drag to reorder" aria-label="Drag to reorder" class="btn-icon cursor-grab active:cursor-grabbing">
                        <x-hugeicons-grip-vertical aria-hidden="true" />
                    </button>
                    <a href="{{ route('collections.edit', ['collection' => $this->definition->key, 'entry' => $entry]) }}" class="flex-1 py-1">
                        <span class="text-sm font-medium text-gray-900">{{ $entry->title }}</span>
                        <span class="ms-2 text-xs text-gray-500">/{{ $entry->slug }}</span>
                    </a>
                    {!! \App\Models\PageStatus::badge($entry->status) !!}
                    @if ($this->canDelete)
                    <button
                        type="button"
                        wire:click="delete({{ $entry->id }})"
                        wire:confirm="Delete &quot;{{ $entry->title }}&quot;? This can't be undone."
                        class="btn-ghost-danger"
                    >
                        Delete
                    </button>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
