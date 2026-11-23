@props(['path', 'index', 'last', 'sideways' => false])

{{-- Move / remove buttons for one repeater row (x-repeater). sideways: left/right arrows for tile grids. --}}
@php
    $swap = fn (int $a, int $b) => "\$wire.\$set('{$path}', (r => ([r[{$a}], r[{$b}]] = [r[{$b}], r[{$a}]], r))([...\$wire.\$get('{$path}')]))";
@endphp

@if ($index > 0)
    <x-icon-button :icon="$sideways ? 'arrow-left-01' : 'arrow-up-01'" :label="$sideways ? 'Move left' : 'Move up'" x-on:click="{{ $swap($index - 1, $index) }}" />
@elseif (! $sideways)
    <span class="size-8" aria-hidden="true"></span>
@endif
@if (! $last)
    <x-icon-button :icon="$sideways ? 'arrow-right-01' : 'arrow-down-01'" :label="$sideways ? 'Move right' : 'Move down'" x-on:click="{{ $swap($index, $index + 1) }}" />
@elseif (! $sideways)
    <span class="size-8" aria-hidden="true"></span>
@endif
<x-icon-button icon="delete-02" label="Remove item" tone="danger" x-on:click="$wire.$set('{{ $path }}', $wire.$get('{{ $path }}').filter((_, n) => n !== {{ $index }}))" />
