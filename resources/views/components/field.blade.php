@props([
    'label' => null,
    'name' => null,
    'required' => false,
    'hint' => null,
    'errorKeys' => null,
    'hideLabel' => false,
    'as' => 'label',
])

{{--
    Label + control + hint + error, the one form row used everywhere.
    Usage: <x-field label="Name" name="name" required><input name="name" class="input"></x-field>
    The wrapper is a <label>, so the control inside is associated without ids. Pass as="div" when the
    slot holds several controls or buttons (image picker, repeater). error-keys overrides which error
    bag keys are shown (defaults to name).
--}}
@php
    $errorKeys = (array) ($errorKeys ?? $name);
@endphp

<{{ $as }} {{ $attributes->class(['block space-y-1.5']) }}>
    @if ($label !== null)
        <span @class(['flex items-baseline gap-1 text-sm font-medium text-foreground', 'sr-only' => $hideLabel])>
            {{ $label }}
            @if ($required)
                <span class="text-danger" aria-hidden="true">*</span>
            @endif
        </span>
    @endif

    {{ $slot }}

    @if ($hint)
        <span class="block text-xs text-secondary-foreground">{{ $hint }}</span>
    @endif

    @foreach (array_filter($errorKeys) as $errorKey)
        @error($errorKey)
            <span class="block text-xs font-medium text-danger">{{ $message }}</span>
        @enderror
    @endforeach
</{{ $as }}>
