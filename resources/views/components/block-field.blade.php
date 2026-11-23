@props(['field', 'path', 'value' => [], 'bare' => false])

{{--
    One schema field bound to wire:model="$path". bare hides the label visually (a repeater row
    with a single field already says what it is). The root id is the anchor the field outline links to.
--}}
@php
    $type = $field['type'];
@endphp

<div {{ $attributes->merge(['id' => 'field-'.str_replace('.', '-', $path)])->class(['scroll-mt-20', 'sm:self-end' => $type === 'boolean']) }}>
    @if ($type === 'image')
        <x-field as="div" :label="$field['label']" :hide-label="$bare" :required="$field['required']" :error-keys="[$path]">
            <livewire:media-picker wire:model="{{ $path }}" :tile="$bare" :key="'media-picker-'.$path" />
        </x-field>
    @elseif ($type === 'repeater')
        <x-repeater :field="$field" :path="$path" :value="is_array($value) ? $value : []" />
    @elseif ($type === 'collection' && $field['collection'])
        <x-field as="div" :label="$field['label']">
            <p class="text-sm text-secondary-foreground">
                Always shows the published entries of
                <a href="{{ route('collections.show', ['collection' => $field['collection']]) }}" class="font-medium text-foreground underline underline-offset-2">{{ \App\Models\CollectionDefinition::where('key', $field['collection'])->value('label') ?? $field['collection'] }}</a>@if ($field['limit']) (first {{ $field['limit'] }})@endif.
            </p>
        </x-field>
    @elseif ($type === 'collection')
        <x-field :label="$field['label']" :required="$field['required']" :error-keys="[$path]">
            <select wire:model="{{ $path }}" class="input">
                <option value="">&mdash;</option>
                @foreach (\App\Models\CollectionDefinition::orderBy('label')->pluck('label', 'key') as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            <span class="block text-xs text-secondary-foreground">
                Shows this collection's published entries @if ($field['limit']) (first {{ $field['limit'] }})@endif.
                <a href="{{ route('collections.index') }}" class="underline underline-offset-2">Manage collections</a>
            </span>
        </x-field>
    @elseif ($type === 'boolean')
        <x-field as="div" :error-keys="[$path]">
            <label class="flex h-[2.375rem] cursor-pointer items-center gap-2.5 rounded-lg bg-input px-3 text-sm font-medium">
                <input type="checkbox" wire:model="{{ $path }}" class="checkbox">
                {{ $field['label'] }}
            </label>
        </x-field>
    @elseif ($type === 'link')
        <x-field as="div" :label="$field['label']" :hide-label="$bare" :required="$field['required']" :error-keys="[$path, $path.'.label', $path.'.url']">
            <div class="grid gap-2 sm:grid-cols-2">
                <input type="text" wire:model="{{ $path }}.label" placeholder="Button text" aria-label="{{ $field['label'] }} text" class="input">
                <input type="text" wire:model="{{ $path }}.url" placeholder="https://... or /page" aria-label="{{ $field['label'] }} URL" class="input">
            </div>
        </x-field>
    @else
        <x-field :label="$field['label']" :hide-label="$bare" :required="$field['required']" :error-keys="[$path]">
            @switch($type)
                @case('textarea')
                    <textarea wire:model="{{ $path }}" rows="3" class="input field-sizing-content min-h-20 max-h-96"></textarea>
                    @break
                @case('richtext')
                    <textarea wire:model="{{ $path }}" rows="6" class="input field-sizing-content min-h-32 max-h-[32rem]"></textarea>
                    @break
                @case('select')
                    <select wire:model="{{ $path }}" class="input">
                        <option value="">&mdash;</option>
                        @foreach ($field['options'] as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                    @break
                @default
                    <input type="text" wire:model="{{ $path }}" class="input">
            @endswitch
        </x-field>
    @endif
</div>
