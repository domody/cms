@props(['fields', 'path', 'values' => []])

{{--
    Lays out schema fields (block content, collection entry data, repeater rows) in order.
    Consecutive short fields (text, select, boolean) pair up in two columns; an odd one out takes
    the full row so no gaps open up. Everything else is full width.
--}}
@php
    $spans = [];
    $run = [];
    $closeRun = function () use (&$run, &$spans): void {
        foreach ($run as $n => $index) {
            $spans[$index] = count($run) % 2 === 1 && $n === count($run) - 1 ? 'full' : 'half';
        }
        $run = [];
    };

    foreach (array_values($fields) as $index => $field) {
        if (in_array($field['type'], ['text', 'select', 'boolean'], true)) {
            $run[] = $index;
        } else {
            $closeRun();
            $spans[$index] = 'full';
        }
    }
    $closeRun();
@endphp

<div {{ $attributes->class(['grid gap-x-4 gap-y-5 sm:grid-cols-2']) }}>
    @foreach (array_values($fields) as $index => $field)
        <x-block-field
            :field="$field"
            :path="$path.'.'.$field['name']"
            :value="$values[$field['name']] ?? []"
            :class="$spans[$index] === 'full' ? 'sm:col-span-2' : ''"
            wire:key="field-{{ $path }}-{{ $field['name'] }}"
        />
    @endforeach
</div>
