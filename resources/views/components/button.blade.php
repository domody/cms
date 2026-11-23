@props(['variant' => 'primary', 'icon' => null, 'href' => null])

{{--
    The app's buttons. Renders an <a> when href is given, otherwise a <button type="button">
    (pass type="submit" for forms). Variants map to the .btn* classes in app.css.
--}}
@php
    $class = match ($variant) {
        'outline' => 'btn-outline',
        'subtle' => 'btn-subtle',
        'danger' => 'btn-danger',
        'danger-outline' => 'btn-danger-outline',
        'ghost' => 'btn-ghost',
        'danger-ghost' => 'btn-ghost-danger',
        default => 'btn',
    };
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class([$class]) }}>
        @if ($icon) <x-dynamic-component :component="'hugeicons-'.$icon" aria-hidden="true" /> @endif
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['type' => 'button'])->class([$class]) }}>
        @if ($icon) <x-dynamic-component :component="'hugeicons-'.$icon" aria-hidden="true" /> @endif
        {{ $slot }}
    </button>
@endif
