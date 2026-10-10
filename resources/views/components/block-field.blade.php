@props(['field', 'path'])

@php
    $inputClass = 'mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10';
@endphp

<div>
    @if ($field['type'] === 'boolean')
        <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
            <input type="checkbox" wire:model="{{ $path }}" class="rounded border-gray-300">
            {{ $field['label'] }}
        </label>
    @else
        <label class="block text-sm font-medium text-gray-700">
            {{ $field['label'] }}@if ($field['required']) <span class="text-red-600">*</span>@endif

            @switch($field['type'])
                @case('textarea')
                    <textarea wire:model="{{ $path }}" rows="4" class="{{ $inputClass }}"></textarea>
                    @break
                @case('richtext')
                    <textarea wire:model="{{ $path }}" rows="8" class="{{ $inputClass }}"></textarea>
                    @break
                @case('select')
                    <select wire:model="{{ $path }}" class="{{ $inputClass }}">
                        <option value="">&mdash;</option>
                        @foreach ($field['options'] as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                    @break
                @case('link')
                    <div class="grid gap-2 sm:grid-cols-2">
                        <input type="text" wire:model="{{ $path }}.label" placeholder="Label" class="{{ $inputClass }}">
                        <input type="text" wire:model="{{ $path }}.url" placeholder="URL" class="{{ $inputClass }}">
                    </div>
                    @break
                @case('image')
                    <input type="text" wire:model="{{ $path }}" placeholder="Image URL" class="{{ $inputClass }}">
                    @break
                @default
                    <input type="text" wire:model="{{ $path }}" class="{{ $inputClass }}">
            @endswitch
        </label>
    @endif

    @foreach ([$path, "$path.label", "$path.url"] as $errorKey)
        @error($errorKey)
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    @endforeach
</div>
