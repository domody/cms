<form id="page-editor-form" data-unsaved-guard wire:submit="save" class="space-y-4">
    <div class="grid gap-4 rounded-lg border border-gray-200 bg-white p-4 sm:grid-cols-3">
        <label class="block text-sm font-medium text-gray-700 sm:col-span-2">
            Title
            <input type="text" wire:model="title" class="input mt-1">
            @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </label>
        <label class="block text-sm font-medium text-gray-700">
            Status
            <select wire:model="status" class="input mt-1">
                @foreach (\App\Models\PageStatus::options() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            @error('status') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </label>
    </div>

    <div class="flex gap-4 items-start justify-start">
        <div class="rounded-lg border border-gray-200 bg-white flex-1">
            <div class="border-b border-gray-100 px-4 py-3">
                <h2 class="text-sm font-semibold text-gray-900">Page Structure</h2>
            </div>

            <div wire:sort="sortBlock">
            @forelse ($blocks as $index => $block)
                @php
                    $definition = $this->definitions->get($block['block_key']);
                    $fieldErrors = collect($errors->keys())->contains(fn ($key) => str_starts_with($key, "blocks.$index."));
                    $isSelected = $block['id'] === $selectedBlockId;
                @endphp
                <div
                    wire:key="block-{{ $block['id'] }}"
                    wire:sort:item="{{ $block['id'] }}"
                    class="flex items-center gap-2 px-3 py-1.5 {{ $loop->last ? '' : 'border-b border-gray-100' }} {{ $isSelected ? 'bg-gray-50' : 'hover:bg-gray-50' }}"
                >
                    <span class="text-end text-xs text-gray-400">{{ $loop->iteration }}</span>
                    <button type="button" wire:click="selectBlock({{ $block['id'] }})" class="flex flex-1 items-center gap-2 py-1 text-start">
                        <span class="text-sm {{ $isSelected ? 'font-semibold' : 'font-medium' }} {{ $block['is_visible'] ? 'text-gray-900' : 'text-gray-400' }}">
                            {{ $definition?->label ?? $block['block_key'] }}
                        </span>
                        @unless ($block['is_visible'])
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">Hidden</span>
                        @endunless
                        @if ($fieldErrors)
                            <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs text-red-700">Needs attention</span>
                        @endif
                    </button>

                    <button type="button" wire:click="toggleVisibility({{ $index }})" class="btn-ghost">
                        {{ $block['is_visible'] ? 'Hide' : 'Show' }}
                    </button>
                    <button type="button" wire:sort:handle title="Drag to reorder" aria-label="Drag to reorder" class="btn-icon cursor-grab active:cursor-grabbing">
                        <x-hugeicons-grip-vertical aria-hidden="true" />
                    </button>
                </div>
            @empty
                <p class="px-4 py-3 text-sm text-gray-500">This page has no blocks.</p>
            @endforelse
            </div>
        </div>

        @foreach ($blocks as $index => $block)
            @if ($block['id'] === $selectedBlockId)
                @php($definition = $this->definitions->get($block['block_key']))
                <div wire:key="editor-{{ $block['id'] }}" class="rounded-lg border border-gray-200 bg-white flex-1">
                    <div class="border-b border-gray-100 px-4 py-3">
                        <h2 class="text-sm font-semibold text-gray-900">{{ $definition?->label ?? $block['block_key'] }}</h2>
                    </div>

                    <div class="space-y-4 px-4 py-4">
                        @if ($definition === null)
                            <p class="text-sm text-amber-700">No definition synced for block type "{{ $block['block_key'] }}", so its content can't be edited.</p>
                        @endif

                        @foreach ($definition?->fields() ?? [] as $field)
                            <x-block-field :field="$field" path="blocks.{{ $index }}.content.{{ $field['name'] }}" :value="$block['content'][$field['name']] ?? []" wire:key="field-{{ $block['id'] }}-{{ $field['name'] }}" />
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    </div>
</form>
