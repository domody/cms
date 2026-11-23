@props(['field', 'value'])

{{-- Read-only rendering of one schema field (page preview). --}}
<div class="grid gap-1 sm:grid-cols-[10rem_minmax(0,1fr)] sm:gap-4">
    <div class="text-sm text-secondary-foreground">{{ $field['label'] }}</div>

    <div class="min-w-0 text-sm">
        @if ($field['type'] === 'collection' && filled($value))
            Published entries of <a href="{{ route('collections.show', ['collection' => $value]) }}" class="font-medium underline underline-offset-2">{{ $value }}</a>
        @elseif (blank($value) && $value !== false)
            <span class="text-muted-foreground">Empty</span>
        @elseif ($field['type'] === 'link')
            <span class="font-medium">{{ $value['label'] ?? $value['url'] }}</span>
            <span class="ms-2 font-mono text-xs text-muted-foreground">{{ $value['url'] ?? '' }}</span>
        @elseif ($field['type'] === 'image')
            @php($media = \App\Models\Media::find(\App\Models\Media::idFrom($value)))
            @if ($media)
                <img src="{{ $media->url() }}" alt="{{ $media->alt }}" class="max-h-40 rounded-lg">
                <div class="mt-1 text-xs text-muted-foreground">{{ $media->original_name }}</div>
            @else
                <span class="text-muted-foreground">Empty</span>
            @endif
        @elseif ($field['type'] === 'repeater')
            {{ count($value) }} {{ \Illuminate\Support\Str::plural('item', count($value)) }}
        @elseif ($field['type'] === 'boolean')
            {{ $value ? 'Yes' : 'No' }}
        @else
            <div class="whitespace-pre-line">{{ $value }}</div>
        @endif
    </div>
</div>
