<form wire:submit="save" class="space-y-4">
    @if (session('status'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-2 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-800">
            Fix the highlighted fields below, then save again.
        </div>
    @endif

    <div class="grid gap-4 rounded-lg border border-gray-200 bg-white p-4 sm:grid-cols-3">
        <label class="block text-sm font-medium text-gray-700 sm:col-span-2">
            Title
            <input type="text" wire:model="title" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
            @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </label>
        <label class="block text-sm font-medium text-gray-700">
            Status
            <select wire:model="status" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                <option value="draft">Draft</option>
                <option value="published">Published</option>
            </select>
            @error('status') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </label>
    </div>

    <div class="space-y-2">
        @foreach ($blocks as $index => $block)
            @php
                $definition = $this->definitions->get($block['block_key']);
                $fieldErrors = collect($errors->keys())->contains(fn ($key) => str_starts_with($key, "blocks.$index."));
            @endphp
            <section
                wire:key="block-{{ $block['id'] }}"
                x-data="{ open: {{ $fieldErrors ? 'true' : 'false' }} }"
                class="rounded-lg border bg-white {{ $block['is_visible'] ? 'border-gray-200' : 'border-dashed border-gray-300' }}"
            >
                <div class="flex items-center gap-2 px-3 py-2">
                    <button type="button" x-on:click="open = ! open" class="flex flex-1 items-center gap-2 text-start">
                        <span class="text-xs text-gray-400" x-text="open ? '−' : '+'"></span>
                        <span class="text-sm font-medium {{ $block['is_visible'] ? 'text-gray-900' : 'text-gray-400' }}">
                            {{ $definition?->label ?? $block['block_key'] }}
                        </span>
                        @unless ($block['is_visible'])
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">Hidden</span>
                        @endunless
                        @if ($fieldErrors)
                            <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs text-red-700">Needs attention</span>
                        @endif
                    </button>

                    <button type="button" wire:click="moveUp({{ $index }})" @disabled($loop->first) title="Move up" class="rounded px-2 py-1 text-gray-600 hover:bg-gray-100 disabled:opacity-30">&uarr;</button>
                    <button type="button" wire:click="moveDown({{ $index }})" @disabled($loop->last) title="Move down" class="rounded px-2 py-1 text-gray-600 hover:bg-gray-100 disabled:opacity-30">&darr;</button>
                    <button type="button" wire:click="toggleVisibility({{ $index }})" class="rounded px-2 py-1 text-xs text-gray-600 hover:bg-gray-100">
                        {{ $block['is_visible'] ? 'Hide' : 'Show' }}
                    </button>
                </div>

                <div x-show="open" x-cloak class="space-y-4 border-t border-gray-100 px-4 py-4">
                    @if ($definition === null)
                        <p class="text-sm text-amber-700">No definition synced for block type "{{ $block['block_key'] }}", so its content can't be edited.</p>
                    @endif

                    @foreach ($definition?->fields() ?? [] as $field)
                        <x-block-field :field="$field" path="blocks.{{ $index }}.content.{{ $field['name'] }}" wire:key="field-{{ $block['id'] }}-{{ $field['name'] }}" />
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" class="rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
            Save changes
        </button>
        <span wire:loading wire:target="save" class="text-sm text-gray-500">Saving&hellip;</span>
    </div>
</form>
