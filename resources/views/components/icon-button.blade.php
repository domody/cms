@props(['icon', 'label', 'tone' => null, 'href' => null])

{{-- Icon-only action (move up/down, remove, drag, close). label becomes aria-label and the hover tooltip. --}}
@php
    $class = ['btn-icon-ghost', 'hover:bg-danger/10 hover:text-danger' => $tone === 'danger'];
@endphp

@if ($href)
    <a href="{{ $href }}" aria-label="{{ $label }}" title="{{ $label }}" {{ $attributes->class($class) }}>
        <x-dynamic-component :component="'hugeicons-'.$icon" aria-hidden="true" />
    </a>
@else
    <button aria-label="{{ $label }}" title="{{ $label }}" {{ $attributes->merge(['type' => 'button'])->class($class) }}>
        <x-dynamic-component :component="'hugeicons-'.$icon" aria-hidden="true" />
    </button>
@endif
