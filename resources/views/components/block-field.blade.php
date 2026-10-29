@props(['field', 'path'])

<div>
    @if ($field['type'] === 'image')
        <div class="block text-sm font-medium text-gray-700">
            {{ $field['label'] }}@if ($field['required']) <span class="text-red-600">*</span>@endif
            <livewire:media-picker wire:model="{{ $path }}" :key="'media-picker-'.$path" />
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
