<div>
    @if ($this->entries->isEmpty())
        <x-empty-state figure="sieve" title="No entries yet">
            Get started and create your first entry.
            <x-slot:actions>
                <x-button icon="add-01" :href="route('collections.create', ['collection' => $this->definition->key])">Add entry</x-button>
            </x-slot:actions>
        </x-empty-state>
    @else
        <x-card :flush="true">
            <ol wire:sort="sortEntry" class="divide-y">
                @foreach ($this->entries as $entry)
                    <li
                        wire:key="entry-{{ $entry->id }}"
                        wire:sort:item="{{ $entry->id }}"
                        class="group flex items-center gap-3 py-1 pe-2 ps-1.5 hover:bg-muted/60"
                    >
                        <x-icon-button icon="grip-vertical" label="Drag to reorder" wire:sort:handle class="cursor-grab text-muted-foreground active:cursor-grabbing" />
                        <a href="{{ route('collections.edit', ['collection' => $this->definition->key, 'entry' => $entry]) }}" class="flex min-w-0 flex-1 items-baseline gap-2 py-2">
                            <span class="truncate text-sm font-medium">{{ $entry->title }}</span>
                            <span class="truncate font-mono text-xs text-muted-foreground">/{{ $entry->slug }}</span>
                        </a>
                        {!! \App\Models\PageStatus::badge($entry->status) !!}
                        @if ($this->canDelete)
                            <x-icon-button
                                icon="delete-02"
                                :label="'Delete '.$entry->title"
                                tone="danger"
                                wire:click="delete({{ $entry->id }})"
                                wire:confirm="Delete &quot;{{ $entry->title }}&quot;? This can't be undone."
                                class="text-muted-foreground sm:opacity-0 sm:group-hover:opacity-100 sm:focus-visible:opacity-100"
                            />
                        @endif
                    </li>
                @endforeach
            </ol>
        </x-card>
    @endif
</div>
