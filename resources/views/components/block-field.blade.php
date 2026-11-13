@props(['field', 'path', 'value' => []])

<div>
    @if ($field['type'] === 'image')
        <div class="block text-sm font-medium text-gray-700">
            {{ $field['label'] }}@if ($field['required']) <span class="text-red-600">*</span>@endif
            <livewire:media-picker wire:model="{{ $path }}" :key="'media-picker-'.$path" />
        </div>
    @elseif ($field['type'] === 'repeater')
        <div class="space-y-2">
            <div class="block text-sm font-medium text-gray-700">
                {{ $field['label'] }}@if ($field['required']) <span class="text-red-600">*</span>@endif
            </div>

            @foreach ($value as $i => $row)
                <div wire:key="{{ $path }}-row-{{ $i }}" class="space-y-3 rounded border border-gray-200 bg-gray-50 p-3">
                    <div class="flex justify-end gap-3 text-xs text-gray-500">
                        @if ($i > 0)
                            <button type="button" class="underline" x-on:click="$wire.$set('{{ $path }}', (r => ([r[{{ $i - 1 }}], r[{{ $i }}]] = [r[{{ $i }}], r[{{ $i - 1 }}]], r))([...$wire.$get('{{ $path }}')]))">Up</button>
                        @endif
                        @if (! $loop->last)
                            <button type="button" class="underline" x-on:click="$wire.$set('{{ $path }}', (r => ([r[{{ $i }}], r[{{ $i + 1 }}]] = [r[{{ $i + 1 }}], r[{{ $i }}]], r))([...$wire.$get('{{ $path }}')]))">Down</button>
                        @endif
                        <button type="button" class="text-red-600 underline" x-on:click="$wire.$set('{{ $path }}', $wire.$get('{{ $path }}').filter((_, n) => n !== {{ $i }}))">Remove</button>
                    </div>
                    @foreach ($field['fields'] as $sub)
                        <x-block-field :field="$sub" path="{{ $path }}.{{ $i }}.{{ $sub['name'] }}" wire:key="{{ $path }}-{{ $i }}-{{ $sub['name'] }}" />
                    @endforeach
                </div>
            @endforeach

            <button type="button" class="btn" x-on:click="$wire.$set('{{ $path }}', [...($wire.$get('{{ $path }}') ?? []), @js(\App\Models\BlockDefinition::blankRow($field))])">Add item</button>
        </div>
    @elseif ($field['type'] === 'collection' && $field['collection'])
        <div class="block text-sm font-medium text-gray-700">
            {{ $field['label'] }}
            <span class="mt-1 block text-xs font-normal text-gray-500">
                Always shows the published entries of
                <a href="{{ route('collections.show', ['collection' => $field['collection']]) }}" class="underline">{{ \App\Models\CollectionDefinition::where('key', $field['collection'])->value('label') ?? $field['collection'] }}</a>@if ($field['limit']) (first {{ $field['limit'] }}) @endif.
            </span>
        </div>
    @elseif ($field['type'] === 'collection')
        <label class="block text-sm font-medium text-gray-700">
            {{ $field['label'] }}@if ($field['required']) <span class="text-red-600">*</span>@endif
            <select wire:model="{{ $path }}" class="input mt-1">
                <option value="">&mdash;</option>
                @foreach (\App\Models\CollectionDefinition::orderBy('label')->pluck('label', 'key') as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            <span class="mt-1 block text-xs font-normal text-gray-500">
                Shows this collection's published entries @if ($field['limit']) (first {{ $field['limit'] }}) @endif.
                <a href="{{ route('collections.index') }}" class="underline">Manage collections</a>
            </span>
        </label>
    @elseif ($field['type'] === 'boolean')
        <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
            <input type="checkbox" wire:model="{{ $path }}" class="checkbox">
            {{ $field['label'] }}
        </label>
    @else
        <label class="block text-sm font-medium text-gray-700">
            {{ $field['label'] }}@if ($field['required']) <span class="text-red-600">*</span>@endif

            @switch($field['type'])
                @case('textarea')
                    <textarea wire:model="{{ $path }}" rows="4" class="input mt-1"></textarea>
                    @break
                @case('richtext')
                    <textarea wire:model="{{ $path }}" rows="8" class="input mt-1"></textarea>
                    @break
                @case('select')
                    <select wire:model="{{ $path }}" class="input mt-1">
                        <option value="">&mdash;</option>
                        @foreach ($field['options'] as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                    @break
                @case('link')
                    <div class="grid gap-2 sm:grid-cols-2">
                        <input type="text" wire:model="{{ $path }}.label" placeholder="Label" class="input mt-1">
                        <input type="text" wire:model="{{ $path }}.url" placeholder="URL" class="input mt-1">
                    </div>
                    @break
                @default
                    <input type="text" wire:model="{{ $path }}" class="input mt-1">
            @endswitch
        </label>
    @endif

    @foreach ([$path, "$path.label", "$path.url"] as $errorKey)
        @error($errorKey)
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    @endforeach
</div>
