@props(['field', 'value'])

<div>
    <div class="text-xs font-medium uppercase tracking-wide text-gray-400">{{ $field['label'] }}</div>

    @if (blank($value) && $value !== false)
        <div class="text-sm italic text-gray-400">empty</div>
    @elseif ($field['type'] === 'link')
        <a href="{{ $value['url'] ?? '#' }}" class="mt-1 inline-block rounded border border-gray-300 px-3 py-1 text-sm text-gray-800" onclick="return false">
            {{ $value['label'] ?? $value['url'] }}
        </a>
        <span class="ms-2 text-xs text-gray-400">{{ $value['url'] ?? '' }}</span>
    @elseif ($field['type'] === 'image')
        <img src="{{ $value }}" alt="" class="mt-1 max-h-40 rounded border border-gray-200">
        <div class="text-xs text-gray-400">{{ $value }}</div>
    @elseif ($field['type'] === 'boolean')
        <div class="text-sm text-gray-900">{{ $value ? 'Yes' : 'No' }}</div>
    @else
        <div class="whitespace-pre-line text-sm text-gray-900">{{ $value }}</div>
    @endif
</div>
