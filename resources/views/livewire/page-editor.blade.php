<form id="page-editor-form" data-unsaved-guard wire:submit="save" class="space-y-6">
    <x-card>
        <div class="grid gap-x-4 gap-y-5 sm:grid-cols-3">
            <x-field label="Title" name="title" class="sm:col-span-2">
                <input type="text" wire:model="title" class="input text-base font-medium">
            </x-field>
            <x-field label="Status" name="status">
                <select wire:model="status" class="input">
                    @foreach (\App\Models\PageStatus::options() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </x-field>
        </div>
    </x-card>

    <div class="grid items-start gap-6 lg:grid-cols-[18rem_minmax(0,1fr)]">
        {{-- Block list: pick one to edit, drag to reorder --}}
        <x-card title="Blocks" :flush="true" class="lg:sticky lg:top-20">
            <x-slot:actions>
                <x-badge>{{ count($blocks) }}</x-badge>
            </x-slot:actions>

            <ol wire:sort="sortBlock" class="p-1.5">
                @forelse ($blocks as $index => $block)
                    @php
                        $definition = $this->definitions->get($block['block_key']);
                        $fieldErrors = collect($errors->keys())->contains(fn ($key) => str_starts_with($key, "blocks.$index."));
                        $isSelected = $block['id'] === $selectedBlockId;
                    @endphp
                    <li
                        wire:key="block-{{ $block['id'] }}"
                        wire:sort:item="{{ $block['id'] }}"
                        @class(['group flex items-center gap-1 rounded-lg ps-2', 'bg-muted' => $isSelected, 'hover:bg-muted/60' => ! $isSelected])
                    >
                        <span class="w-4 shrink-0 text-xs tabular-nums text-muted-foreground">{{ $loop->iteration }}</span>
                        <button type="button" wire:click="selectBlock({{ $block['id'] }})" @if ($isSelected) aria-current="true" @endif class="flex min-w-0 flex-1 items-center gap-2 py-2 text-start">
                            <span @class([
                                'truncate text-sm',
                                'font-semibold' => $isSelected,
                                'font-medium' => ! $isSelected,
                                'text-muted-foreground line-through decoration-muted-foreground/50' => ! $block['is_visible'],
                            ])>{{ $definition?->label ?? $block['block_key'] }}</span>
                            @if ($fieldErrors)
                                <x-badge tone="danger">Fix</x-badge>
                            @endif
                        </button>
                        <x-icon-button
                            :icon="$block['is_visible'] ? 'view' : 'view-off'"
                            :label="$block['is_visible'] ? 'Hide block' : 'Show block'"
                            wire:click="toggleVisibility({{ $index }})"
                            :class="$block['is_visible'] ? 'text-muted-foreground sm:opacity-0 sm:group-hover:opacity-100 sm:focus-visible:opacity-100' : 'text-muted-foreground'"
                        />
                        <x-icon-button icon="grip-vertical" label="Drag to reorder" wire:sort:handle class="cursor-grab text-muted-foreground active:cursor-grabbing" />
                    </li>
                @empty
                    <li>
                        <x-empty-state figure="exploded" title="No blocks yet">
                            Blocks appear once your frontend declares them for this page.
                        </x-empty-state>
                    </li>
                @endforelse
            </ol>
        </x-card>

        {{-- Selected block's fields --}}
        @foreach ($blocks as $index => $block)
            @if ($block['id'] === $selectedBlockId)
                @php($definition = $this->definitions->get($block['block_key']))
                <x-card wire:key="editor-{{ $block['id'] }}" :title="$definition?->label ?? $block['block_key']" :description="$block['is_visible'] ? null : 'Hidden: the frontend doesn\'t receive this block.'">
                    <x-slot:actions>
                        <x-button variant="ghost" :icon="$block['is_visible'] ? 'view-off' : 'view'" wire:click="toggleVisibility({{ $index }})">
                            {{ $block['is_visible'] ? 'Hide' : 'Show' }}
                        </x-button>
                    </x-slot:actions>

                    @if ($definition === null)
                        <x-callout tone="warning">No definition synced for block type "{{ $block['block_key'] }}", so its content can't be edited.</x-callout>
                    @else
                        <x-field-list :fields="$definition->fields()" :path="'blocks.'.$index.'.content'" :values="$block['content']" />
                    @endif
                </x-card>
            @endif
        @endforeach
    </div>
</form>
